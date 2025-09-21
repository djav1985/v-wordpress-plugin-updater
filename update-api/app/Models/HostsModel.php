<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: HostsModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\DatabaseManager;
use App\Helpers\Encryption;

class HostsModel
{
    /**
     * Return all host entries.
     *
     * @return array<int, string>
     */
    public static function getEntries(): array
    {
        $conn = DatabaseManager::getConnection();
        $rows = $conn->fetchAllAssociative('SELECT domain, key FROM hosts ORDER BY domain');
        $entries = [];
        foreach ($rows as $row) {
            $entries[] = $row['domain'] . ' ' . $row['key'];
        }
        return $entries;
    }

    /**
     * Add an entry to the hosts table.
     */
    public static function addEntry(string $domain, string $key): bool
    {
        $encrypted = Encryption::encrypt($key);
        $conn = DatabaseManager::getConnection();
        return $conn->executeStatement('INSERT INTO hosts (domain, key, send_auth) VALUES (?, ?, 1)', [$domain, $encrypted]) > 0;
    }

    /**
     * Update an entry in the hosts table.
     */
    public static function updateEntry(int $line, string $domain, string $key): bool
    {
        $encrypted = Encryption::encrypt($key);
        $conn = DatabaseManager::getConnection();
        return $conn->executeStatement('REPLACE INTO hosts (domain, key, send_auth) VALUES (?, ?, 1)', [$domain, $encrypted]) > 0;
    }

    /**
     * Delete an entry from the hosts table.
     */
    public static function deleteEntry(int $line, string $domain): bool
    {
        $conn = DatabaseManager::getConnection();
        $result = $conn->executeStatement('DELETE FROM hosts WHERE domain = ?', [$domain]) > 0;
        if ($result) {
            $conn->executeStatement('DELETE FROM logs WHERE domain = ?', [$domain]);
        }
        return $result;
    }

    /**
     * Mark send_auth flag for a domain.
     */
    public static function markSendAuth(string $domain): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('UPDATE hosts SET send_auth = 1 WHERE domain = ?', [$domain]);
    }

    /**
     * Retrieve key if send_auth is set and toggle it off.
     */
    public static function getKeyIfSendAuth(string $domain): ?string
    {
        $conn = DatabaseManager::getConnection();
        $row = $conn->fetchAssociative('SELECT key, send_auth FROM hosts WHERE domain = ?', [$domain]);
        if ($row && (int) $row['send_auth'] === 1) {
            $conn->executeStatement('UPDATE hosts SET send_auth = 0 WHERE domain = ?', [$domain]);
            return Encryption::decrypt($row['key']);
        }
        return null;
    }

    /**
     * Check if a key update is pending for a domain.
     */
    public static function isKeyUpdatePending(string $domain): bool
    {
        $conn = DatabaseManager::getConnection();
        $row = $conn->fetchAssociative('SELECT old_key FROM hosts WHERE domain = ? AND old_key IS NOT NULL AND old_key != ""', [$domain]);
        return $row !== false;
    }

    /**
     * Initiate key update by setting send_auth and storing old key.
     */
    public static function initiateKeyUpdate(string $domain, string $newKey): bool
    {
        $conn = DatabaseManager::getConnection();
        // First get the current key to store as old key
        $row = $conn->fetchAssociative('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        if (!$row) {
            return false;
        }
        
        $oldKey = $row['key'];
        $newEncryptedKey = Encryption::encrypt($newKey);
        
        // Update with new key, store old key, and set send_auth
        return $conn->executeStatement(
            'UPDATE hosts SET key = ?, old_key = ?, send_auth = 1 WHERE domain = ?',
            [$newEncryptedKey, $oldKey, $domain]
        ) > 0;
    }

    /**
     * Validate old key and complete key update process.
     */
    public static function validateAndCompleteKeyUpdate(string $domain, string $providedOldKey): ?string
    {
        $conn = DatabaseManager::getConnection();
        $row = $conn->fetchAssociative('SELECT key, old_key FROM hosts WHERE domain = ?', [$domain]);
        
        if (!$row || !$row['old_key']) {
            return null;
        }
        
        $storedOldKey = Encryption::decrypt($row['old_key']);
        if ($storedOldKey === $providedOldKey) {
            // Clear old_key and return new key
            $conn->executeStatement('UPDATE hosts SET old_key = NULL WHERE domain = ?', [$domain]);
            return Encryption::decrypt($row['key']);
        }
        
        return null;
    }
}
