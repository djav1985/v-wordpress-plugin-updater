<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ApiController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Utility;
use App\Core\ErrorMiddleware;
use App\Core\Controller;

class ApiController extends Controller
{
    public static function handleRequest(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (Utility::isBlacklisted($ip) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(403);
            ErrorMiddleware::logMessage('Forbidden or invalid request from ' . $ip);
            exit();
        }

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
                ErrorMiddleware::logMessage('Bad request missing parameter: ' . $p);
                exit();
            }
            $values[] = $_GET[$p];
        }
        list($type, $domain, $key, $slug, $version) = $values;

        $domain  = Utility::validateDomain($domain);
        $key     = Utility::validateKey($key);
        $slug    = Utility::validateSlug($slug);
        $version = Utility::validateVersion($version);

        $invalid = [];
        if ($domain === null) {
            $invalid[] = 'domain';
        }
        if ($key === null) {
            $invalid[] = 'key';
        }
        if ($slug === null) {
            $invalid[] = 'slug';
        }
        if ($version === null) {
            $invalid[] = 'version';
        }
        if (!empty($invalid)) {
            http_response_code(400);
            ErrorMiddleware::logMessage('Bad request invalid parameter: ' . implode(', ', $invalid));
            exit();
        }

        if ($type === 'theme') {
            $dir = THEMES_DIR;
            $log = LOG_DIR . '/theme.log';
        }
        if ($type === 'plugin') {
            $dir = PLUGINS_DIR;
            $log = LOG_DIR . '/plugin.log';
        }

        $host_file = @fopen(HOSTS_ACL . '/HOSTS', 'r');
        if ($host_file) {
            while (($line = fgets($host_file)) !== false) {
                $line = trim($line);
                if ($line) {
                    list($host, $host_key) = explode(' ', $line, 2);
                    $host_key = Utility::decrypt($host_key);
                    if ($host === $domain && $host_key !== null && $host_key === $key) {
                        fclose($host_file);
                        foreach (scandir($dir) as $filename) {
                            if ($filename === '.' || $filename === '..') {
                                continue;
                            }
                            if (strpos($filename, $slug) === 0) {
                                $filename_parts = explode('_', $filename);
                                if (isset($filename_parts[1]) && version_compare($filename_parts[1], $version, '>')) {
                                    $file_path = $dir . '/' . $filename;
                                    if (is_file($file_path)) {
                                        header('Content-Type: application/octet-stream');
                                        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                                        header('Content-Length: ' . filesize($file_path));
                                        readfile($file_path);
                                        $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Successful';
                                        file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
                                        ErrorMiddleware::logMessage($log_message, 'info');
                                        exit();
                                    }
                                }
                            }
                        }
                        http_response_code(204);
                        $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Successful';
                        file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
                        ErrorMiddleware::logMessage($log_message, 'info');
                        exit();
                    }
                }
            }
            fclose($host_file);
        }

        http_response_code(403);
        $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Failed';
        file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
        ErrorMiddleware::logMessage($log_message);
        exit();
    }
}
