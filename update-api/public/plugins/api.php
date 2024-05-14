<?php

/**
 * WP Plugin Update API
 * Version: 1.1
 * Author: Vontainment
 * Author URI: https://vontainment.com
 * /public/plugins/api.php
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
    $plugin_slug = $sanitized_get['plugin'] ?? '';
    $plugin_version = $sanitized_get['version'] ?? '';

    // Check if the domain and key exist in the HOSTS file
    if ($host_file = @fopen(HOSTS_ACL . 'HOSTS', 'r')) {
        while ($line = fgets($host_file)) {
            $line = trim($line);
            list($host, $host_key) = explode(' ', $line);
            if ($host === $domain && $host_key === $key) {
                // The domain and key pair exists in the HOSTS file, so check for an updated plugin version
                $plugins = scandir(PLUGINS_DIR);
                foreach ($plugins as $filename) {
                    if (strpos($filename, $plugin_slug) === 0) {
                        $filename_parts = explode('_', $filename);
                        if (isset($filename_parts[1]) && version_compare($filename_parts[1], $plugin_version, '>')) {
                            $zip_url = 'http://' . $_SERVER['HTTP_HOST'] . '/plugins/download.php?domain=' . $domain . '&key=' . $key . '&file=' . $filename;
                            header('Content-Type: application/json');
                            echo json_encode(['zip_url' => $zip_url]);
                            log_message($domain, 'Successful');
                            exit();
                        }
                    }
                }
                // The plugin version is not higher than the installed version, so return an empty response
                http_response_code(204);
                header('Content-Type: application/json');
                header('Content-Length: 0');
                log_message($domain, 'Successful');
                exit();
            }
        }
        fclose($host_file);
    }

    update_failed_attempts($ip);
    // The domain and key pair does not exist in the HOSTS file, log the unauthorized access and return a 401 Unauthorized response
    http_response_code(401);
    echo 'Unauthorized';
    log_error('Unauthorized access', $domain);
    exit();
}

function log_message($domain, $status)
{
    $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' ' . $status;
    file_put_contents(LOG_DIR . '/plugin.log', $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
}

function log_error($message, $domain)
{
    error_log($message . ': ' . $_SERVER['REMOTE_ADDR']);
    $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Failed';
    file_put_contents(LOG_DIR . '/plugin.log', $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
}

exit();
