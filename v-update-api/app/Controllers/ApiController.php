<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: ApiController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Helpers\ValidationHelper;
use App\Helpers\EncryptionHelper;
use App\Models\BlacklistModel;
use App\Models\HostsModel;
use App\Models\PluginModel;
use App\Models\ThemeModel;
use App\Models\LogModel;
use App\Core\ErrorManager;
use App\Core\ResponseManager;

class ApiController
{
    /**
     * Handle the incoming update API request.
     *
     * Validates the request parameters, authenticates the host domain/key pair,
     * and returns:
     * - 200 with ZIP when a newer version is available
     * - 204 when no update is available
     * - 400 for malformed input
     * - 403 on authentication failure
     * - 404 when the slug is unknown for an authenticated host
     *
     * @return ResponseManager
     */
    public function handleRequest(): ResponseManager
    {
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            ErrorManager::log('Forbidden: missing or invalid IP address');
            return new ResponseManager(403);
        }

        if (BlacklistModel::isBlacklisted($ip)) {
            ErrorManager::log('Forbidden: blacklisted IP ' . $ip);
            return new ResponseManager(403);
        }

        if ($method !== 'GET') {
            ErrorManager::log('Method not allowed for API request: ' . $method . ' from ' . $ip);
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
                ErrorManager::log('Bad request missing parameter: ' . $p);
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
            ErrorManager::log('Bad request invalid parameter: ' . implode(', ', $invalid));
            return new ResponseManager(400);
        }

        $dir = $type === 'theme' ? THEMES_DIR : PLUGINS_DIR;

        $encryptedHostKey = HostsModel::getEncryptedKeyByDomain($domain);
        if ($encryptedHostKey === null) {
            // Unknown domain is an authentication failure and contributes to lockout budget.
            BlacklistModel::updateFailedAttempts($ip);
            LogModel::addLog($domain, $type, 'Failed');
            ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Failed');
            return new ResponseManager(403);
        }

        $hostKey = EncryptionHelper::decrypt($encryptedHostKey);
        if ($hostKey === null || !hash_equals($hostKey, $key)) {
            // Credential mismatch is an authentication failure and contributes to lockout budget.
            BlacklistModel::updateFailedAttempts($ip);
            LogModel::addLog($domain, $type, 'Failed');
            ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Failed');
            return new ResponseManager(403);
        }

        // Migrate legacy CBC-encrypted key to AEAD on successful auth.
        if (EncryptionHelper::needsMigration($encryptedHostKey)) {
            HostsModel::updateEntry($domain, $hostKey);
        }

        if ($type === 'theme') {
            $dbVersion = ThemeModel::getVersionBySlug($slug);
        } else {
            $dbVersion = PluginModel::getVersionBySlug($slug);
        }
        if ($dbVersion === null) {
            ErrorManager::log('Not found: unknown ' . $type . ' slug "' . $slug . '" for ' . $domain);
            return new ResponseManager(404);
        }

        if (version_compare($dbVersion, $version, '>')) {
            $filePath = $dir . '/' . $slug . '_' . $dbVersion . '.zip';
            $contentLength = @filesize($filePath);
            if (is_file($filePath) && is_readable($filePath) && is_int($contentLength)) {
                LogModel::addLog($domain, $type, 'Success');
                ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                return ResponseManager::file($filePath, 'application/octet-stream')
                    ->withAddedHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
                    ->withAddedHeader('Content-Length', (string) $contentLength);
            }
            ErrorManager::log('Update file unavailable or unreadable: ' . $filePath);
            return new ResponseManager(500);
        }

        LogModel::addLog($domain, $type, 'Success');
        ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
        return new ResponseManager(204);
    }
}
