<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: HomeController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\SessionManager;
use App\Helpers\ValidationHelper;
use App\Helpers\EncryptionHelper;
use App\Core\ErrorManager;
use App\Models\HostsModel;
use App\Helpers\MessageHelper;
use App\Core\Response;
use App\Helpers\RequestInput;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    private const REVEAL_WINDOW_SECONDS = 30;

    /**
     * Handles GET requests for managing hosts.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $this->pruneExpiredReveals();
        return Response::view('home', [
            'hosts' => $this->getHosts(),
        ]);
    }

    /**
     * Handles POST submissions for host actions.
     *
    * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $post = $request->getParsedBody();
        if (!is_array($post)) {
            return new Response(400);
        }
        $token = RequestInput::string($post, 'csrf_token') ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return Response::redirect('/home');
        }

        $domain = ValidationHelper::validateDomain(RequestInput::string($post, 'domain') ?? '');
        if (isset($post['add_entry'])) {
            $newKey = ValidationHelper::generateKey();
            if ($domain !== null && HostsModel::addEntry($domain, $newKey)) {
                MessageHelper::addMessage('Entry added successfully.');
            } else {
                $error = 'Failed to add entry.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($post['regen_entry'])) {
            $newKey = ValidationHelper::generateKey();
            if ($domain !== null && HostsModel::updateEntry($domain, $newKey)) {
                MessageHelper::addMessage('Key regenerated successfully.');
            } else {
                $error = 'Failed to regenerate key.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($post['reveal_entry'])) {
            if ($domain !== null && $this->revealKey($domain)) {
                MessageHelper::addMessage('Key revealed for 30 seconds.');
            } else {
                $error = 'Failed to reveal key.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($post['delete_entry'])) {
            if ($domain !== null && HostsModel::deleteEntry($domain)) {
                MessageHelper::addMessage('Entry deleted successfully.');
            } else {
                $error = 'Failed to delete entry.';
                error_log($error);
                MessageHelper::addMessage($error);
            }
        }
        return Response::redirect('/home');
    }

    /**
     * Prepare host rows for the view, including the short-lived key reveal state.
     *
     * @return list<array{
     *     domain: string,
     *     display_key: string,
     *     masked_key: string,
     *     expires_at: int,
     *     key_input_id: string,
     *     revealed: bool
     * }>
     */
    private function getHosts(): array
    {
        $entries = HostsModel::getEntries();
        $hosts = [];
        foreach ($entries as $index => $entry) {
            $domain = $entry['domain'];
            $encryptedKey = $entry['key'];
            $key = EncryptionHelper::decrypt($encryptedKey) ?? '';
            if ($key !== '') {
                HostsModel::migrateLegacyKey($domain, $encryptedKey, $key);
            }

            $revealed = $this->getRevealedKey($domain);
            $isRevealed = false;
            $displayKey = $this->maskKey($key);
            $expiresAt = 0;
            if ($revealed !== null && $revealed['key'] === $key) {
                $isRevealed = true;
                $displayKey = $key;
                $expiresAt = $revealed['expires_at'];
            }
            $hosts[] = [
                'domain' => $domain,
                'display_key' => $displayKey,
                'masked_key' => $this->maskKey($key),
                'expires_at' => $expiresAt,
                'key_input_id' => 'host-key-' . $index,
                'revealed' => $isRevealed,
            ];
        }

        return $hosts;
    }

    /**
     * Reveal a host key for a short duration.
     */
    private function revealKey(string $domain): bool
    {
        $decrypted = $this->lookupDecryptedKey($domain);
        if ($decrypted === null || $decrypted === '') {
            return false;
        }

        $revealed = SessionManager::getInstance()->get('revealed_keys', []);
        if (!is_array($revealed)) {
            $revealed = [];
        }
        $revealed[$domain] = [
            'key' => $decrypted,
            'expires_at' => time() + self::REVEAL_WINDOW_SECONDS,
        ];
        SessionManager::getInstance()->set('revealed_keys', $revealed);
        return true;
    }

    /**
     * Look up and decrypt a host key by domain.
     *
     * @param string $domain Domain name.
     * @return string|null Decrypted key or null if not found.
     */
    private function lookupDecryptedKey(string $domain): ?string
    {
        foreach (HostsModel::getEntries() as $entry) {
            $entryDomain = $entry['domain'];
            if ($entryDomain !== $domain) {
                continue;
            }

            $encryptedKey = $entry['key'];
            $key = EncryptionHelper::decrypt($encryptedKey);
            if ($key !== null && $key !== '') {
                HostsModel::migrateLegacyKey($domain, $encryptedKey, $key);
            }
            return $key;
        }

        return null;
    }

    /**
     * Return revealed key metadata when still valid.
     *
     * @return array{key: string, expires_at: int}|null
     */
    private function getRevealedKey(string $domain): ?array
    {
        $revealed = SessionManager::getInstance()->get('revealed_keys', []);
        if (!is_array($revealed) || !isset($revealed[$domain]) || !is_array($revealed[$domain])) {
            return null;
        }

        $entry = $revealed[$domain];
        $key = $entry['key'] ?? null;
        $expiresAt = $entry['expires_at'] ?? 0;
        if (!is_string($key) || !is_int($expiresAt) || $expiresAt < time()) {
            return null;
        }

        return [
            'key' => $key,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Purge expired key-reveal entries from session.
     */
    private function pruneExpiredReveals(): void
    {
        $revealed = SessionManager::getInstance()->get('revealed_keys', []);
        if (!is_array($revealed)) {
            SessionManager::getInstance()->set('revealed_keys', []);
            return;
        }

        $filtered = [];
        $now = time();
        foreach ($revealed as $domain => $entry) {
            if (!is_string($domain) || !is_array($entry)) {
                continue;
            }
            $key = $entry['key'] ?? null;
            $expiresAt = $entry['expires_at'] ?? null;
            if (!is_string($key) || !is_int($expiresAt) || $expiresAt < $now) {
                continue;
            }
            $filtered[$domain] = $entry;
        }
        SessionManager::getInstance()->set('revealed_keys', $filtered);
    }

    /**
     * Mask a key for default UI rendering.
     */
    private function maskKey(string $key): string
    {
        if ($key === '') {
            return '••••••••';
        }
        return str_repeat('•', max(8, strlen($key)));
    }
}
