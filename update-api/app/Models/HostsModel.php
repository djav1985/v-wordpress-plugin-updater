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
}
