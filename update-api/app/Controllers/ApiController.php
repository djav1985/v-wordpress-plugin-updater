<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: ApiController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Helpers\Validation;
use App\Helpers\Encryption;
use App\Models\Blacklist;
use App\Models\HostsModel;
use App\Core\ErrorManager;
use App\Core\Controller;
use App\Core\DatabaseManager;
use App\Core\Response;

class ApiController extends Controller
{
    public function handleRequest(): Response
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (Blacklist::isBlacklisted($ip) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            ErrorManager::getInstance()->log('Forbidden or invalid request from ' . $ip);
            return new Response(403);
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
                ErrorManager::getInstance()->log('Bad request missing parameter: ' . $p);
                return new Response(400);
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
            ErrorManager::getInstance()->log('Bad request invalid parameter: ' . implode(', ', $invalid));
            return new Response(400);
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
                            $headers = [
                                'Content-Type' => 'application/octet-stream',
                                'Content-Disposition' => 'attachment; filename="' . basename($file_path) . '"',
                                'Content-Length' => (string) filesize($file_path),
                            ];
                            $conn->executeStatement(
                                'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                                [$domain, $type, date('Y-m-d'), 'Success']
                            );
                            ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                            return Response::file($file_path, $headers);
                        }
                    }
                    $conn->executeStatement(
                        'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                        [$domain, $type, date('Y-m-d'), 'Success']
                    );
                    ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                    return new Response(204);
                }
            }
        }

        // Increment failed attempts for this IP (may blacklist after threshold)
        Blacklist::updateFailedAttempts($ip);

        $conn->executeStatement(
            'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
            [$domain, $type, date('Y-m-d'), 'Failed']
        );
        ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Failed');
        return new Response(403);
    }
}
