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

use App\Core\DatabaseManager;
use App\Core\ErrorManager;

class BlacklistModel
{
    /**
     * Determine whether the current request originates from a blacklisted IP.
     *
     * @return bool True when remote IP is valid and blacklisted.
     */
    public static function isCurrentRequestIpBlacklisted(): bool
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if (!$ip) {
            return false;
        }

        if (self::isBlacklisted($ip)) {
            ErrorManager::log("Blacklisted IP attempted access: $ip", 'error');
            return true;
        }

        return false;
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
    public static function updateFailedAttempts(string $ip): void
    {
        $now  = time();

        // Single atomic statement: insert on first attempt, or increment the
        // counter and conditionally set blacklisted/timestamp on conflict.
        DatabaseManager::getInstance()->getConnection()->executeStatement(
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
    public static function isBlacklisted(string $ip): bool
    {
        $record = DatabaseManager::getInstance()->getConnection()->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);

        if ($record && (int) $record['blacklisted'] === 1) {
            if (time() - (int) $record['timestamp'] > 7 * 24 * 60 * 60) {
                DatabaseManager::getInstance()->getConnection()->update('blacklist', [
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

    /**
     * Cleanup expired blacklist entries.
     *
     * @return void
     */
    public static function cleanup(): void
    {
        $currentTime = time();
        $sevenDaysAgo = $currentTime - (7 * 24 * 60 * 60);
        $threeDaysAgo = $currentTime - (3 * 24 * 60 * 60);

        // Remove IPs that were blocked more than 7 days ago
        DatabaseManager::getInstance()->getConnection()->executeStatement(
            'DELETE FROM blacklist WHERE blacklisted = 1 AND timestamp < ?',
            [$sevenDaysAgo]
        );

        // Remove IPs that are not blocked and haven't been updated in 3 days
        DatabaseManager::getInstance()->getConnection()->executeStatement(
            'DELETE FROM blacklist WHERE blacklisted = 0 AND timestamp < ?',
            [$threeDaysAgo]
        );
    }
}



