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
use App\Core\DatabaseManager;

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

        $dir = $type === 'theme' ? THEMES_DIR : PLUGINS_DIR;

        $conn = DatabaseManager::getConnection();
        $hostRow = $conn->fetchAssociative('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        if ($hostRow) {
            $host_key = Encryption::decrypt($hostRow['key']);
            if ($host_key !== null && $host_key === $key) {
                $table = $type === 'theme' ? 'themes' : 'plugins';
                $row = $conn->fetchAssociative("SELECT version FROM $table WHERE slug = ?", [$slug]);
                if ($row) {
                    $dbVersion = $row['version'];
                    if (version_compare($dbVersion, $version, '>')) {
                        $file_path = $dir . '/' . $slug . '_' . $dbVersion . '.zip';
                        if (is_file($file_path)) {
                            header('Content-Type: application/octet-stream');
                            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                            header('Content-Length: ' . filesize($file_path));
                            readfile($file_path);
                            $conn->executeStatement(
                                'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                                [$domain, $type, date('Y-m-d'), 'Success']
                            );
                            ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                            exit();
                        }
                    }
                    http_response_code(204);
                    $conn->executeStatement(
                        'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                        [$domain, $type, date('Y-m-d'), 'Success']
                    );
                    ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                    exit();
                }
            }
        }

        http_response_code(403);
        $conn->executeStatement(
            'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
            [$domain, $type, date('Y-m-d'), 'Failed']
        );
        ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Failed');
        exit();
    }
}
