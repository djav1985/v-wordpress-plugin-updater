<?php

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: UtilityHandler.php
 * Description: Security utilities (moved from waf-lib.php)
 */


class UtilityHandler
{
    /**
     * Validate a domain string
     */
    public static function validateDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $domain : null;
    }

    /**
     * Validate an API key or generic token
     */
    public static function validateKey(string $key): ?string
    {
        $key = trim($key);
        return preg_match('/^[A-Za-z0-9_-]+$/', $key) ? $key : null;
    }

    /**
     * Validate plugin or theme names and slugs
     */
    public static function validateSlug(string $slug): ?string
    {
        $slug = basename(trim($slug));
        return preg_match('/^[A-Za-z0-9._-]+$/', $slug) ? $slug : null;
    }

    /**
     * Validate uploaded file names
     */
    public static function validateFilename(string $filename): ?string
    {
        $filename = basename(trim($filename));
        return preg_match('/^[A-Za-z0-9._-]+$/', $filename) ? $filename : null;
    }

    /**
     * Validate a version number such as 1.0.0
     */
    public static function validateVersion(string $version): ?string
    {
        $version = trim($version);
        return preg_match('/^\d+(?:\.\d+)*$/', $version) ? $version : null;
    }

    /**
     * Validate usernames for the admin interface
     */
    public static function validateUsername(string $username): ?string
    {
        $username = trim($username);
        return preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username) ? $username : null;
    }

    /**
     * Basic password validation
     */
    public static function validatePassword(string $password): ?string
    {
        $password = trim($password);
        return strlen($password) >= 6 ? $password : null;
    }

    /**
     * Update the number of failed login attempts for an IP address and blacklist if necessary.
     * Handles file errors and uses file locking for concurrency.
     *
     * @param string $ip
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
     * Check if an IP address is blacklisted. If the blacklist has expired, reset blacklist and login_attempts.
     * Handles file errors and uses file locking for concurrency.
     *
     * @param string $ip
     * @return bool
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
