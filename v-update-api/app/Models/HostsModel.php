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

use App\Helpers\EncryptionHelper;
use Doctrine\DBAL\Connection;

class HostsModel
{
    public function __construct(private Connection $connection)
    {
    }
    /**
     * Return encrypted host key for a domain, or null when not found.
     */
    public function getEncryptedKeyByDomain(string $domain): ?string
    {
        $row = $this->connection->fetchAssociative('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        if ($row === false || !isset($row['key'])) {
            return null;
        }

        return (string) $row['key'];
    }

    /**
     * Return all host entries.
     *
     * @return array<int, array{domain: string, key: string}>
     */
    public function getEntries(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT domain, key FROM hosts ORDER BY domain');
        return array_map(
            fn (array $row): array => [
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
    public function getHosts(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT domain FROM hosts ORDER BY domain');
        $hosts = [];
        foreach ($rows as $row) {
            $hosts[] = $row['domain'];
        }
        return $hosts;
    }

    /**
     * Add an entry to the hosts table.
     */
    public function addEntry(string $domain, string $key): bool
    {
        $encrypted = EncryptionHelper::encrypt($key);
        return $this->connection->executeStatement('INSERT INTO hosts (domain, key) VALUES (?, ?)', [$domain, $encrypted]) > 0;
    }

    /**
     * Update an entry in the hosts table.
     */
    public function updateEntry(string $domain, string $key): bool
    {
        $encrypted = EncryptionHelper::encrypt($key);
        return $this->connection->executeStatement('UPDATE hosts SET key = ? WHERE domain = ?', [$encrypted, $domain]) > 0;
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
    public function migrateLegacyKey(string $domain, string $encryptedKey, string $plainKey): void
    {
        if (!EncryptionHelper::needsMigration($encryptedKey)) {
            return;
        }
        $this->connection->executeStatement(
            'UPDATE hosts SET key = ? WHERE domain = ?',
            [EncryptionHelper::encrypt($plainKey), $domain]
        );
    }

    /**
     * Delete an entry from the hosts table.
     */
    public function deleteEntry(string $domain): bool
    {
        $result = $this->connection->executeStatement('DELETE FROM hosts WHERE domain = ?', [$domain]) > 0;
        if ($result) {
            $this->connection->executeStatement('DELETE FROM logs WHERE domain = ?', [$domain]);
        }
        return $result;
    }
}
