<?php

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
use App\Core\Response;
use App\Helpers\RequestInput;
use Psr\Http\Message\ServerRequestInterface;

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
    * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $serverParams = $request->getServerParams();
        $ip     = RequestInput::string($serverParams, 'REMOTE_ADDR') ?? '';
        $method = $request->getMethod();

        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            ErrorManager::log('Forbidden: missing or invalid IP address');
            return new Response(403);
        }

        if (BlacklistModel::isBlacklisted($ip)) {
            ErrorManager::log('Forbidden: blacklisted IP ' . $ip);
            return new Response(403);
        }

        if ($method !== 'GET') {
            ErrorManager::log('Method not allowed for API request: ' . $method . ' from ' . $ip);
            return new Response(405);
        }

        $params = [
                   'type',
                   'domain',
                   'key',
                   'slug',
                   'version',
                  ];
        $query = $request->getQueryParams();
        $values = [];
        foreach ($params as $p) {
            $value = RequestInput::string($query, $p);
            if ($value === null || $value === '' || ($p === 'type' && !in_array($value, ['plugin', 'theme'], true))) {
                ErrorManager::log('Bad request missing or malformed parameter: ' . $p);
                return new Response(400);
            }
            $values[] = $value;
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
            return new Response(400);
        }

        $dir = $type === 'theme' ? THEMES_DIR : PLUGINS_DIR;

        $encryptedHostKey = HostsModel::getEncryptedKeyByDomain($domain);
        if ($encryptedHostKey === null) {
            // Unknown domain is an authentication failure and contributes to lockout budget.
            BlacklistModel::updateFailedAttempts($ip);
            LogModel::addLog($domain, $type, 'Failed');
            ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Failed');
            return new Response(403);
        }

        $hostKey = EncryptionHelper::decrypt($encryptedHostKey);
        if ($hostKey === null || !hash_equals($hostKey, $key)) {
            // Credential mismatch is an authentication failure and contributes to lockout budget.
            BlacklistModel::updateFailedAttempts($ip);
            LogModel::addLog($domain, $type, 'Failed');
            ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Failed');
            return new Response(403);
        }

        // Migrate legacy CBC-encrypted key to AEAD on successful auth.
        if (EncryptionHelper::needsMigration($encryptedHostKey)) {
            HostsModel::updateEntry($domain, $hostKey);
        }

        if ($type === 'theme') {
            $availableVersion = ThemeModel::getVersionBySlug($slug);
        } else {
            $availableVersion = PluginModel::getVersionBySlug($slug);
        }
        if ($availableVersion === null) {
            ErrorManager::log('Not found: unknown ' . $type . ' slug "' . $slug . '" for ' . $domain);
            return new Response(404);
        }

        if (version_compare($availableVersion, $version, '>')) {
            $filePath = $dir . '/' . $slug . '_' . $availableVersion . '.zip';
            $contentLength = @filesize($filePath);
            if (is_file($filePath) && is_readable($filePath) && is_int($contentLength)) {
                LogModel::addLog($domain, $type, 'Success');
                ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
                return Response::file($filePath, 'application/octet-stream')
                    ->withAddedHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
                    ->withAddedHeader('Content-Length', (string) $contentLength);
            }
            ErrorManager::log('Update file unavailable or unreadable: ' . $filePath);
            return new Response(500);
        }

        LogModel::addLog($domain, $type, 'Success');
        ErrorManager::log($domain . ' ' . date('Y-m-d') . ' Successful', 'info');
        return new Response(204);
    }
}
