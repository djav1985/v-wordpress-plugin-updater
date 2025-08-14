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

use App\Helpers\Validation;
use App\Helpers\Encryption;
use App\Models\Blacklist;
use App\Core\ErrorManager;
use App\Core\Controller;

class ApiController extends Controller
{
    public function handleRequest(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (Blacklist::isBlacklisted($ip) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(403);
            ErrorManager::getInstance()->log('Forbidden or invalid request from ' . $ip);
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
                ErrorManager::getInstance()->log('Bad request missing parameter: ' . $p);
                exit();
            }
            $values[] = $_GET[$p];
        }
        list($type, $domain, $key, $slug, $version) = $values;

        $domain  = Validation::validateDomain($domain);
        $key     = Validation::validateKey($key);
        $slug    = Validation::validateSlug($slug);
        $version = Validation::validateVersion($version);

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
            ErrorManager::getInstance()->log('Bad request invalid parameter: ' . implode(', ', $invalid));
            exit();
        }

        if ($type === 'theme') {
            $dir = THEMES_DIR;
            $log = LOG_DIR . '/theme.log';
        } else {
            // Fallback to plugins directory; $type is validated earlier.
            $dir = PLUGINS_DIR;
            $log = LOG_DIR . '/plugin.log';
        }

        $host_file = @fopen(HOSTS_ACL . '/HOSTS', 'r');
        if ($host_file) {
            while (($line = fgets($host_file)) !== false) {
                $line = trim($line);
                if ($line) {
                    list($host, $host_key) = explode(' ', $line, 2);
                    $host_key = Encryption::decrypt($host_key);
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
                                        ErrorManager::getInstance()->log($log_message, 'info');
                                        exit();
                                    }
                                }
                            }
                        }
                        http_response_code(204);
                        $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Successful';
                        file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
                        ErrorManager::getInstance()->log($log_message, 'info');
                        exit();
                    }
                }
            }
            fclose($host_file);
        }

        http_response_code(403);
        $log_message = $domain . ' ' . date('Y-m-d,h:i:sa') . ' Failed';
        file_put_contents($log, $log_message . PHP_EOL, LOCK_EX | FILE_APPEND);
        ErrorManager::getInstance()->log($log_message);
        exit();
    }
}
