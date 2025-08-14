<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Utility.php
 * Description: WordPress Update API
 */

namespace App\Core;

class Utility
{
    /**
     * Validate a domain string.
     *
     * @param string $domain The domain to validate.
     * @return string|null The validated domain or null if invalid.
     */
    public static function validateDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));

        // Ensure the domain contains at least one dot and valid characters
        if (preg_match('/^(?!-)[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/', $domain)) {
            return $domain;
        }

        return null;
    }

    /**
     * Validate an API key or generic token.
     *
     * @param string $key The key to validate.
     * @return string|null The validated key or null if invalid.
     */
    public static function validateKey(string $key): ?string
    {
        $key = trim($key);
        return preg_match('/^[A-Za-z0-9_-]+$/', $key) ? $key : null;
    }

    /**
     * Validate plugin or theme names and slugs.
     *
     * @param string $slug The slug to validate.
     * @return string|null The validated slug or null if invalid.
     */
    public static function validateSlug(string $slug): ?string
    {
        $slug = basename(trim($slug));
        return preg_match('/^[A-Za-z0-9._-]+$/', $slug) ? $slug : null;
    }

    /**
     * Validate uploaded file names.
     *
     * @param string $filename The filename to validate.
     * @return string|null The validated filename or null if invalid.
     */
    public static function validateFilename(string $filename): ?string
    {
        $filename = basename(trim($filename));
        return preg_match('/^[A-Za-z-]+_[0-9.]+\.zip$/', $filename) ? $filename : null;
    }

    /**
     * Validate a version number such as 1.0.0.
     *
     * @param string $version The version to validate.
     * @return string|null The validated version or null if invalid.
     */
    public static function validateVersion(string $version): ?string
    {
        $version = trim($version);
        return preg_match('/^\d+(?:\.\d+)*$/', $version) ? $version : null;
    }

    /**
     * Validate usernames for the admin interface.
     *
     * @param string $username The username to validate.
     * @return string|null The validated username or null if invalid.
     */
    public static function validateUsername(string $username): ?string
    {
        $username = trim($username);
        return preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username) ? $username : null;
    }

    /**
     * Basic password validation.
     *
     * @param string $password The password to validate.
     * @return string|null The validated password or null if invalid.
     */
    public static function validatePassword(string $password): ?string
    {
        $password = trim($password);
        return strlen($password) >= 6 ? $password : null;
    }

    /**
     * Get a PDO connection to the local SQLite database.
     *
     * @return \PDO The initialized PDO instance.
     */
    private static function getDatabase(): \PDO
    {
        $pdo = new \PDO('sqlite:' . DATABASE_FILE);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS blacklist (
            ip TEXT PRIMARY KEY,
            login_attempts INTEGER NOT NULL,
            blacklisted INTEGER NOT NULL,
            timestamp INTEGER NOT NULL
        )');

        return $pdo;
    }

    /**
     * Update the number of failed login attempts for an IP address and blacklist if necessary.
     *
     * @param string $ip The IP address to update.
     * @return void
     */
    public static function updateFailedAttempts(string $ip): void
    {
        $pdo = self::getDatabase();

        $stmt = $pdo->prepare('SELECT login_attempts FROM blacklist WHERE ip = :ip');
        $stmt->execute([':ip' => $ip]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $attempts = (int) $row['login_attempts'] + 1;
            $blacklisted = $attempts >= 3 ? 1 : 0;
            $pdo->prepare('UPDATE blacklist SET login_attempts = :attempts, blacklisted = :blacklisted, timestamp = :ts WHERE ip = :ip')
                ->execute([
                    ':attempts'   => $attempts,
                    ':blacklisted' => $blacklisted,
                    ':ts'        => time(),
                    ':ip'        => $ip,
                ]);
        } else {
            $pdo->prepare('INSERT INTO blacklist (ip, login_attempts, blacklisted, timestamp) VALUES (:ip, 1, 0, :ts)')
                ->execute([
                    ':ip' => $ip,
                    ':ts' => time(),
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
        $pdo = self::getDatabase();

        $stmt = $pdo->prepare('SELECT login_attempts, blacklisted, timestamp FROM blacklist WHERE ip = :ip');
        $stmt->execute([':ip' => $ip]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row && (int) $row['blacklisted'] === 1) {
            if (time() - (int) $row['timestamp'] > (3 * 24 * 60 * 60)) {
                $pdo->prepare('UPDATE blacklist SET blacklisted = 0, login_attempts = 0 WHERE ip = :ip')
                    ->execute([':ip' => $ip]);
                return false;
            }
            return true;
        }

        return false;
    }
}
