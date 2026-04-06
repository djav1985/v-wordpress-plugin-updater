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
use App\Core\ResponseManager;

class ApiController extends Controller
{
    /**
     * Handle the incoming update API request.
     *
     * Validates the request parameters, authenticates the host domain/key pair,
     * and returns the update ZIP when a newer version is available, 204 when
     * the client is already up-to-date, or 403 on authentication failure.
     *
     * @return ResponseManager
     */
    public function handleRequest(): ResponseManager
    {
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            ErrorManager::getInstance()->log('Forbidden: missing or invalid IP address');
            return new ResponseManager(403);
        }

        if (BlacklistModel::isBlacklisted($ip)) {
            ErrorManager::getInstance()->log('Forbidden: blacklisted IP ' . $ip);
            return new ResponseManager(403);
        }

        if ($method !== 'GET') {
            ErrorManager::getInstance()->log('Method not allowed for API request: ' . $method . ' from ' . $ip);
            return new ResponseManager(405);
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
                return new ResponseManager(400);
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
            return new ResponseManager(400);
        }

        $dir = $type === 'theme' ? THEMES_DIR : PLUGINS_DIR;

        $conn = DatabaseManager::getConnection();
        $hostRow = $conn->fetchAssociative('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        if ($hostRow) {
            $hostKey = EncryptionHelper::decrypt($hostRow['key']);
            if ($hostKey !== null && $hostKey === $key) {
                // Migrate legacy CBC-encrypted key to AEAD on successful auth.
                if (EncryptionHelper::needsMigration($hostRow['key'])) {
                    $conn->executeStatement(
                        'UPDATE hosts SET key = ? WHERE domain = ?',
                        [EncryptionHelper::encrypt($hostKey), $domain]
                    );
                }
                $table = $type === 'theme' ? 'themes' : 'plugins';
                $row = $conn->fetchAssociative("SELECT version FROM $table WHERE slug = ?", [$slug]);
                if ($row) {
                    $dbVersion = $row['version'];
                    if (version_compare($dbVersion, $version, '>')) {
                        $filePath = $dir . '/' . $slug . '_' . $dbVersion . '.zip';
                        $contentLength = @filesize($filePath);
                        if (is_file($filePath) && is_readable($filePath) && is_int($contentLength)) {
                            $conn->executeStatement(
                                'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                                [$domain, $type, date('Y-m-d'), 'Success']
                            );
                            ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                            return ResponseManager::file($filePath, 'application/octet-stream')
                                ->withAddedHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
                                ->withAddedHeader('Content-Length', (string) $contentLength);
                        }
                        ErrorManager::getInstance()->log('Update file unavailable or unreadable: ' . $filePath);
                        return new ResponseManager(500);
                    }
                    $conn->executeStatement(
                        'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                        [$domain, $type, date('Y-m-d'), 'Success']
                    );
                    ErrorManager::getInstance()->log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                    return new ResponseManager(204);
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
        return new ResponseManager(403);
    }
}
