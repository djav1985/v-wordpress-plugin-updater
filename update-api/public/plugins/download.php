<?php

/**
 * WP Plugin Update API
 * Version: 1.1
 * Author: Vontainment
 * Author URI: https://vontainment.com
 * /public/plugins/download.php
 */

// Include the config file
require_once __DIR__ .  '/../../config.php';
require_once __DIR__ .  '/../../lib/waf-lib.php';

$ip = $_SERVER['REMOTE_ADDR'];

if (is_blacklisted($ip)) {
    // Stop the script and show an error if the IP is blacklisted
    http_response_code(403); // Optional: Set HTTP status code to 403 Forbidden
    echo "Your IP address has been blacklisted. If you believe this is an error, please contact us.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sanitized_get = array_map('sanitize_input', $_GET);
    $domain = $sanitized_get['domain'] ?? '';
    $key = $sanitized_get['key'] ?? '';
    $file = $sanitized_get['file'] ?? '';

    // Validate the domain and key against the HOSTS file
    if (validate_domain_key($domain, $key)) {
        $file_path = PLUGINS_DIR . '/' . $file;
        if (file_exists($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            http_response_code(404);
            echo 'File not found';
            error_log('File not found: ' . $file_path);
            exit();
        }
    } else {
        update_failed_attempts($ip);
        http_response_code(401);
        echo 'Unauthorized';
        error_log('Unauthorized access: ' . $_SERVER['REMOTE_ADDR']);
        exit();
    }
}

function validate_domain_key($domain, $key)
{
    if ($handle = fopen(HOSTS_ACL . 'HOSTS', 'r')) {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (!empty($line)) {
                list($host, $host_key) = explode(' ', $line, 2);
                if ($domain == $host && $key == $host_key) {
                    fclose($handle);
                    return true;
                }
            }
        }
        fclose($handle);
    }
    return false;
}

exit();
