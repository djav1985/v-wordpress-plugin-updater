<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: BlacklistModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use Doctrine\DBAL\Connection;

class BlacklistModel
{
    public function __construct(private Connection $connection)
    {
    }
    /**
     * Update the number of failed login attempts for an IP address and blacklist if necessary.
     *
     * Uses an atomic UPSERT so concurrent requests cannot race on the
     * read-modify-write cycle.
     *
     * @param string $ip The IP address to update.
     * @return void
     */
    public function updateFailedAttempts(string $ip): void
    {
        $now  = time();

        // Single atomic statement: insert on first attempt, or increment the
        // counter and conditionally set blacklisted/timestamp on conflict.
        $this->connection->executeStatement(
            'INSERT INTO blacklist (ip, login_attempts, blacklisted, timestamp)
             VALUES (?, 1, 0, ?)
             ON CONFLICT(ip) DO UPDATE SET
                 login_attempts = blacklist.login_attempts + 1,
                 blacklisted = CASE
                     WHEN blacklist.login_attempts + 1 >= 3 THEN 1
                     ELSE blacklist.blacklisted
                 END,
                 timestamp = ?',
            [$ip, $now, $now]
        );
    }

    /**
     * Check if an IP address is blacklisted. If the blacklist has expired, reset blacklist and login attempts.
     *
     * @param string $ip The IP address to check.
     * @return bool True if the IP is blacklisted, false otherwise.
     */
    public function isBlacklisted(string $ip): bool
    {
        $record = $this->connection->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);

        if ($record && (int) $record['blacklisted'] === 1) {
            if (time() - (int) $record['timestamp'] > 7 * 24 * 60 * 60) {
                $this->connection->update('blacklist', [
                    'blacklisted'   => 0,
                    'login_attempts' => 0,
                    'timestamp'     => time(),
                ], ['ip' => $ip]);
                return false;
            }
            return true;
        }
        return false;
    }
}
