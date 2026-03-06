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

use App\Helpers\ValidationHelper;
use App\Helpers\EncryptionHelper;
use App\Models\BlacklistModel;
use App\Models\HostsModel;
use App\Core\ErrorManager;
use App\Core\Controller;
use App\Core\DatabaseManager;
use App\Core\Response;

class ApiController extends Controller
{
    /**
     * Handle the incoming update API request.
     *
     * Validates the request parameters, authenticates the host domain/key pair,
     * and returns the update ZIP when a newer version is available, 204 when
     * the client is already up-to-date, or 403 on authentication failure.
     *
     * @return Response
     */
    public function handleRequest(): Response
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (BlacklistModel::isBlacklisted($ip) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
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

        $domain  = ValidationHelper::validateDomain($domain);
        $key     = ValidationHelper::validateKey($key);
        $slug    = ValidationHelper::validateSlug($slug);
        $version = ValidationHelper::validateVersion($version);

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
            $hostKey = EncryptionHelper::decrypt($hostRow['key']);
            if ($hostKey !== null && $hostKey === $key) {
                $table = $type === 'theme' ? 'themes' : 'plugins';
                $row = $conn->fetchAssociative("SELECT version FROM $table WHERE slug = ?", [$slug]);
                if ($row) {
                    $dbVersion = $row['version'];
                    if (version_compare($dbVersion, $version, '>')) {
                        $filePath = $dir . '/' . $slug . '_' . $dbVersion . '.zip';
                        if (is_file($filePath)) {
                            $conn->executeStatement(
                                'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                                [$domain, $type, date('Y-m-d'), 'Success']
                            );
                            ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                            return Response::file($filePath, 'application/octet-stream')
                                ->withAddedHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
                                ->withAddedHeader('Content-Length', (string) filesize($filePath));
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
        BlacklistModel::updateFailedAttempts($ip);

        $conn->executeStatement(
            'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
            [$domain, $type, date('Y-m-d'), 'Failed']
        );
        ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Failed');
        return new Response(403);
    }
}
