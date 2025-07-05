<?php

/**
 * WP Plugin/Theme Update API (Combined)
 * Version: 1.1
 * Author: Vontainment
 * Author URI: https://vontainment.com
 */

require_once '../config.php';
require_once '../lib/class-lib.php';


$ip = $_SERVER['REMOTE_ADDR'];
if (SecurityHandler::isBlacklisted($ip) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(403);
    ErrorHandler::logMessage('Forbidden or invalid request from ' . $ip);
    exit();
} else {
    // Sanitize, extract, and set required GET parameters, error if any missing or invalid
    $params = [
               'type',
               'domain',
               'key',
               'slug',
               'version',
              ];
    $values = [];
    foreach ($params as $p) {
        if (!isset($_GET[$p]) || $_GET[$p] === '' || ($p === 'type' && !in_array($_GET[$p], ['plugin', 'theme']))) {
            http_response_code(400);
            ErrorHandler::logMessage('Bad request missing parameter: ' . $p);
            exit();
        }
        $values[] = $_GET[$p];
    }
    list($type, $domain, $key, $slug, $version) = $values;
    $domain = SecurityHandler::validateDomain($domain);
    $key = SecurityHandler::validateKey($key);
    $slug = SecurityHandler::validateSlug($slug);
    $version = SecurityHandler::validateVersion($version);

    if ($type === 'theme') {
        $dir = THEMES_DIR;
        $log = LOG_DIR . '/theme.log';
    }

    if ($type === 'plugin') {
        $dir = PLUGINS_DIR;
        $log = LOG_DIR . '/plugin.log';
    }

// Validate domain and key with if/else only, no boolean flag
    $host_file = @fopen(HOSTS_ACL . 'HOSTS', 'r');
    if ($host_file) {
        while (($line = fgets($host_file)) !== false) {
            $line = trim($line);
            if ($line) {
                list($host, $host_key) = explode(' ', $line);
                if ($host === $domain && $host_key === $key) {
                    fclose($host_file);
                    // Find and serve update if available
                    foreach (scandir($dir) as $filename) {
                        if (strpos($filename, $slug) === 0) {
                            $filename_parts = explode('_', $filename);
                            if (isset($filename_parts[1]) && version_compare($filename_parts[1], $version, '>')) {
                                $file_path = $dir . '/' . $filename;
                                if (file_exists($file_path)) {
                                    header('Content-Type: application/octet-stream');
                                    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                                    header('Content-Length: ' . filesize($file_path));
                                    readfile($file_path);
                                    $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Successful';
                                    file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
                                    ErrorHandler::logMessage($log_message, 'info');
                                    exit();
                                }
                            }
                        }
                    }
                    // No update available
                    http_response_code(204);
                    $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Successful';
                    file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
                    ErrorHandler::logMessage($log_message, 'info');
                    exit();
                }
            }
        }
        fclose($host_file);
    }
    http_response_code(403);
    $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Failed';
    file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
    ErrorHandler::logMessage($log_message);
    exit();
}
