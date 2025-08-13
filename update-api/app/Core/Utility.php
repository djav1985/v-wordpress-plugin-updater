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
     * Generate a random API key.
     *
     * @param int $length Desired length of the key.
     * @return string Generated key consisting of hex characters.
     */
    public static function generateKey(int $length = 32): string
    {
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
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
     * Encrypt a string using AES-256-CBC.
     *
     * @param string $plain Plain text to encrypt.
     * @return string Base64-encoded cipher text.
     */
    public static function encrypt(string $plain): string
    {
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = random_bytes($iv_length);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    /**
     * Decrypt a string encrypted with encrypt().
     *
     * @param string $cipher Base64-encoded cipher text.
     * @return string|null Decrypted plain text or null on failure.
     */
    public static function decrypt(string $cipher): ?string
    {
        $data = base64_decode($cipher, true);
        if ($data === false) {
            return null;
        }
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($data) <= $iv_length) {
            return null;
        }
        $iv = substr($data, 0, $iv_length);
        $cipher_text = substr($data, $iv_length);
        $plain = openssl_decrypt(
            $cipher_text,
            'aes-256-cbc',
            hash('sha256', ENCRYPTION_KEY, true),
            OPENSSL_RAW_DATA,
            $iv
        );
        return $plain === false ? null : $plain;
    }

    /**
     * Update the number of failed login attempts for an IP address and blacklist if necessary.
     *
     * @param string $ip The IP address to update.
     * @return void
     */
    public static function updateFailedAttempts(string $ip): void
    {
        $blacklist_file = BLACKLIST_DIR . "/BLACKLIST.json";
        $content = [];
        if (file_exists($blacklist_file)) {
            $raw = @file_get_contents($blacklist_file);
            if ($raw !== false) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $content = $json;
                }
            }
        }

        if (isset($content[$ip])) {
            $content[$ip]['login_attempts'] += 1;
            if ($content[$ip]['login_attempts'] >= 3) {
                $content[$ip]['blacklisted'] = true;
                $content[$ip]['timestamp'] = time();
            }
        } else {
            $content[$ip] = [
                             'login_attempts' => 1,
                             'blacklisted'    => false,
                             'timestamp'      => time(),
                            ];
        }

        $fp = fopen($blacklist_file, 'c+');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode($content));
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
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
        $blacklist_file = BLACKLIST_DIR . "/BLACKLIST.json";
        $blacklist = [];
        if (file_exists($blacklist_file)) {
            $raw = @file_get_contents($blacklist_file);
            if ($raw !== false) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $blacklist = $json;
                }
            }
        }

        if (isset($blacklist[$ip]) && $blacklist[$ip]['blacklisted']) {
            // Check if the timestamp is older than three days
            if (time() - $blacklist[$ip]['timestamp'] > (3 * 24 * 60 * 60)) {
                // Remove the IP address from the blacklist and reset login_attempts
                $blacklist[$ip]['blacklisted'] = false;
                $blacklist[$ip]['login_attempts'] = 0;
                $fp = fopen($blacklist_file, 'c+');
                if ($fp) {
                    if (flock($fp, LOCK_EX)) {
                        ftruncate($fp, 0);
                        rewind($fp);
                        fwrite($fp, json_encode($blacklist));
                        fflush($fp);
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                }
            } else {
                return true;
            }
        }
        return false;
    }
}
