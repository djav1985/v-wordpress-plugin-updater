<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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

use App\Helpers\ValidationHelper;
use App\Helpers\EncryptionHelper;
use App\Core\ErrorManager;
use App\Models\HostsModel;
use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use App\Core\ResponseManager;

class HomeController
{
    private const REVEAL_WINDOW_SECONDS = 30;

    /**
     * Handles GET requests for managing hosts.
     */
    public function handleRequest(): ResponseManager
    {
        $this->pruneExpiredReveals();
        return ResponseManager::view('home', [
            'hostsTableHtml' => $this->getHostsTableHtml(),
        ]);
    }

    /**
     * Handles POST submissions for host actions.
     */
    public function handleSubmission(): ResponseManager
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return ResponseManager::redirect('/home');
        }

        $domain = isset($_POST['domain']) ? ValidationHelper::validateDomain($_POST['domain']) : null;
        if (isset($_POST['add_entry'])) {
            $newKey = ValidationHelper::generateKey();
            if ($domain !== null && HostsModel::addEntry($domain, $newKey)) {
                MessageHelper::addMessage('Entry added successfully.');
            } else {
                $error = 'Failed to add entry.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($_POST['regen_entry'])) {
            $newKey = ValidationHelper::generateKey();
            if ($domain !== null && HostsModel::updateEntry($domain, $newKey)) {
                MessageHelper::addMessage('Key regenerated successfully.');
            } else {
                $error = 'Failed to regenerate key.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($_POST['reveal_entry'])) {
            if ($domain !== null && $this->revealKey($domain)) {
                MessageHelper::addMessage('Key revealed for 30 seconds.');
            } else {
                $error = 'Failed to reveal key.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($_POST['delete_entry'])) {
            if ($domain !== null && HostsModel::deleteEntry($domain)) {
                MessageHelper::addMessage('Entry deleted successfully.');
            } else {
                $error = 'Failed to delete entry.';
                error_log($error);
                MessageHelper::addMessage($error);
            }
        }
        return ResponseManager::redirect('/home');
    }

    /**
     * Generates an HTML table row for a host entry.
     */
    private function generateHostsTableRow(int $lineNumber, string $domain, string $key): string
    {
        $revealed = $this->getRevealedKey($domain);
        $showingPlaintext = $revealed !== null && $revealed['key'] === $key;
        $displayKey = $showingPlaintext ? $key : $this->maskKey($key);
        $expiresAt = $showingPlaintext ? (int) $revealed['expires_at'] : 0;
        $copyDisabled = $showingPlaintext ? '' : ' disabled';
        $rowId = 'host-key-' . $lineNumber;

        return '<tr>
            <form method="post" action="/home">
                <input type="hidden" name="csrf_token" value="' .
                    htmlspecialchars(SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8') . '">
                <td><input class="hosts-domain" type="text" name="domain" value="' .
                htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') .
            '" readonly></td>
                <td>
                    <input id="' . htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8') . '" class="hosts-key" type="text" value="' .
                    htmlspecialchars($displayKey, ENT_QUOTES, 'UTF-8') .
                '" data-masked-value="' . htmlspecialchars($this->maskKey($key), ENT_QUOTES, 'UTF-8') . '" data-expires-at="' .
                    htmlspecialchars((string) $expiresAt, ENT_QUOTES, 'UTF-8') . '" readonly>
                </td>
                <td>
                    <input class="hosts-submit" type="submit" name="reveal_entry" value="Reveal">
                    <button class="hosts-submit copy-key-btn" type="button" data-copy-target="' .
                        htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8') . '"' . $copyDisabled . '>Copy</button>
                    <input class="hosts-submit" type="submit" name="regen_entry" value="Regen">
                    <input class="hosts-submit" type="submit" name="delete_entry" value="Delete">
                </td>
            </form>
        </tr>';
    }

    /**
     * Generates the hosts table HTML for display.
     */
    private function getHostsTableHtml(): string
    {
        $entries = HostsModel::getEntries();
        $hostsTableHtml = '';
        if (count($entries) > 0) {
            $halfCount = (int) ceil(count($entries) / 2);
            $entriesColumn1 = array_slice($entries, 0, $halfCount);
            $entriesColumn2 = array_slice($entries, $halfCount);
            $hostsTableHtml .= '<div class="row">';
            // Column 1
            $hostsTableHtml .= '<div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Key</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($entriesColumn1 as $index => $entry) {
                $lineNumber = $index; // Correct line number for column 1
                $domain = $entry['domain'] ?? '';
                $encryptedKey = $entry['key'] ?? '';
                $key = EncryptionHelper::decrypt($encryptedKey) ?? '';
                if ($key !== '') {
                    HostsModel::migrateLegacyKey($domain, $encryptedKey, $key);
                }
                $hostsTableHtml .= $this->generateHostsTableRow($lineNumber, $domain, $key);
            }

            $hostsTableHtml .= '</tbody></table></div>';
            // Column 2
            $hostsTableHtml .= '<div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Key</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($entriesColumn2 as $index => $entry) {
                $lineNumber = $index + $halfCount; // Correct line number for column 2
                $domain = $entry['domain'] ?? '';
                $encryptedKey = $entry['key'] ?? '';
                $key = EncryptionHelper::decrypt($encryptedKey) ?? '';
                if ($key !== '') {
                    HostsModel::migrateLegacyKey($domain, $encryptedKey, $key);
                }
                $hostsTableHtml .= $this->generateHostsTableRow($lineNumber, $domain, $key);
            }

            $hostsTableHtml .= '</tbody></table></div></div>';
        } else {
            $hostsTableHtml = "No entries found.";
        }
        return $hostsTableHtml;
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

        $session = SessionManager::getInstance();
        $revealed = $session->get('revealed_keys', []);
        if (!is_array($revealed)) {
            $revealed = [];
        }
        $revealed[$domain] = [
            'key' => $decrypted,
            'expires_at' => time() + self::REVEAL_WINDOW_SECONDS,
        ];
        $session->set('revealed_keys', $revealed);
        return true;
    }

    /**
     * Look up and decrypt a host key by domain.
     */
    private function lookupDecryptedKey(string $domain): ?string
    {
        foreach (HostsModel::getEntries() as $entry) {
            $entryDomain = $entry['domain'] ?? '';
            if ($entryDomain !== $domain) {
                continue;
            }

            $encryptedKey = $entry['key'] ?? '';
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
        $session = SessionManager::getInstance();
        $revealed = $session->get('revealed_keys', []);
        if (!is_array($revealed)) {
            $session->set('revealed_keys', []);
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
        $session->set('revealed_keys', $filtered);
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
