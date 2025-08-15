<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: Blacklist.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\DatabaseManager;

class Blacklist
{
    /**
     * Get database connection.
     */
    private static function getConnection(): \Doctrine\DBAL\Connection
    {
        return DatabaseManager::getConnection();
    }

    /**
     * Update the number of failed login attempts for an IP address and blacklist if necessary.
     *
     * @param string $ip The IP address to update.
     * @return void
     */
    public static function updateFailedAttempts(string $ip): void
    {
        $conn = self::getConnection();
        $record = $conn->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);

        if ($record) {
            $loginAttempts = (int) $record['login_attempts'] + 1;
            $blacklisted = (int) $record['blacklisted'];
            $timestamp = (int) $record['timestamp'];

            if ($loginAttempts >= 3) {
                $blacklisted = 1;
                $timestamp = time();
            }

            $conn->update('blacklist', [
                'login_attempts' => $loginAttempts,
                'blacklisted'   => $blacklisted,
                'timestamp'     => $timestamp,
            ], ['ip' => $ip]);
        } else {
            $conn->insert('blacklist', [
                'ip'             => $ip,
                'login_attempts' => 1,
                'blacklisted'    => 0,
                'timestamp'      => time(),
            ]);
        }
    }

    /**
     * Check if an IP address is blacklisted. If the blacklist has expired, reset blacklist and login attempts.
     *
     * @param string $ip The IP address to check.
     * @return bool True if the IP is blacklisted, false otherwise.
     */
    public static function isBlacklisted(string $ip): bool
    {
        $conn = self::getConnection();
        $record = $conn->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);

        if ($record && (int) $record['blacklisted'] === 1) {
            if (time() - (int) $record['timestamp'] > 3 * 24 * 60 * 60) {
                $conn->update('blacklist', [
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
