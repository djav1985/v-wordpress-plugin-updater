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
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $encrypted = Encryption::encrypt($safe_key);
        $conn = DatabaseManager::getConnection();
        return $conn->executeStatement('INSERT INTO hosts (domain, key) VALUES (?, ?)', [$safe_domain, $encrypted]) > 0;
    }

    /**
     * Update an entry in the hosts table.
     */
    public static function updateEntry(int $line, string $domain, string $key): bool
    {
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $encrypted = Encryption::encrypt($safe_key);
        $conn = DatabaseManager::getConnection();
        return $conn->executeStatement('REPLACE INTO hosts (domain, key) VALUES (?, ?)', [$safe_domain, $encrypted]) > 0;
    }

    /**
     * Delete an entry from the hosts table.
     */
    public static function deleteEntry(int $line, string $domain): bool
    {
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $conn = DatabaseManager::getConnection();
        $result = $conn->executeStatement('DELETE FROM hosts WHERE domain = ?', [$safe_domain]) > 0;
        if ($result) {
            $conn->executeStatement('DELETE FROM logs WHERE domain = ?', [$safe_domain]);
        }
        return $result;
    }
}
