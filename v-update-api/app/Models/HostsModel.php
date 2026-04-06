<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: HostsModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\DatabaseManager;
use App\Helpers\EncryptionHelper;

class HostsModel
{
    /**
     * Return all host entries.
     *
     * @return array<int, array{domain: string, key: string}>
     */
    public static function getEntries(): array
    {
        $conn = DatabaseManager::getConnection();
        $rows = $conn->fetchAllAssociative('SELECT domain, key FROM hosts ORDER BY domain');
        return array_map(
            static fn (array $row): array => [
                'domain' => (string) $row['domain'],
                'key' => (string) $row['key'],
            ],
            $rows
        );
    }

    /**
     * Return all hosts (domain only).
     *
     * @return array<int, string>
     */
    public static function getHosts(): array
    {
        $conn = DatabaseManager::getConnection();
        $rows = $conn->fetchAllAssociative('SELECT domain FROM hosts ORDER BY domain');
        $hosts = [];
        foreach ($rows as $row) {
            $hosts[] = $row['domain'];
        }
        return $hosts;
    }

    /**
     * Add an entry to the hosts table.
     */
    public static function addEntry(string $domain, string $key): bool
    {
        $encrypted = EncryptionHelper::encrypt($key);
        $conn = DatabaseManager::getConnection();
        return $conn->executeStatement('INSERT INTO hosts (domain, key) VALUES (?, ?)', [$domain, $encrypted]) > 0;
    }

    /**
     * Update an entry in the hosts table.
     */
    public static function updateEntry(string $domain, string $key): bool
    {
        $encrypted = EncryptionHelper::encrypt($key);
        $conn = DatabaseManager::getConnection();
        return $conn->executeStatement('UPDATE hosts SET key = ? WHERE domain = ?', [$encrypted, $domain]) > 0;
    }

    /**
     * Re-encrypt a host's key with AEAD if it is still stored with the legacy
     * CBC scheme.  Safe to call on every read; no-ops when the key is already
     * AEAD-encrypted.
     *
     * @param string $domain       The host domain used as the primary key.
     * @param string $encryptedKey The currently stored (possibly legacy) ciphertext.
     * @param string $plainKey     The already-decrypted plain-text key.
     */
    public static function migrateLegacyKey(string $domain, string $encryptedKey, string $plainKey): void
    {
        if (!EncryptionHelper::needsMigration($encryptedKey)) {
            return;
        }
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement(
            'UPDATE hosts SET key = ? WHERE domain = ?',
            [EncryptionHelper::encrypt($plainKey), $domain]
        );
    }

    /**
     * Delete an entry from the hosts table.
     */
    public static function deleteEntry(string $domain): bool
    {
        $conn = DatabaseManager::getConnection();
        $result = $conn->executeStatement('DELETE FROM hosts WHERE domain = ?', [$domain]) > 0;
        if ($result) {
            $conn->executeStatement('DELETE FROM logs WHERE domain = ?', [$domain]);
        }
        return $result;
    }
}
