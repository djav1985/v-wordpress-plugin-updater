<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: security.php
 * Description: Security utilities (moved from waf-lib.php)
 */

namespace UpdateApi\util;

class Security
{
    /**
     * Sanitize and validate input data
     *
     * @param string $data
     * @return string
     */
    public static function sanitizeInput($data)
    {
        $data = trim(strip_tags($data));
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $data = str_replace(array("<?", "?>", "<%", "%>"), "", $data);
        $data = str_replace(array("<script", "</script"), "", $data);
        $data = str_replace(array("/bin/sh", "exec(", "system(", "passthru(", "shell_exec(", "phpinfo("), "", $data);
        return $data;
    }

    /**
     * Check if a string contains a disallowed character
     *
     * @param string $str
     * @param array $disallowedChars
     * @return bool
     */
    public static function containsDisallowedChars($str, $disallowedChars)
    {
        if (!is_array($disallowedChars)) {
            return false;
        }
        foreach ($disallowedChars as $char) {
            if (strpos($str, $char) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a string contains a disallowed pattern
     *
     * @param string $str
     * @param array $disallowedPatterns
     * @return bool
     */
    public static function containsDisallowedPatterns($str, $disallowedPatterns)
    {
        if (!is_array($disallowedPatterns)) {
            return false;
        }
        foreach ($disallowedPatterns as $pattern) {
            if (strpos($str, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update the number of failed login attempts for an IP address and blacklist if necessary.
     * Handles file errors and uses file locking for concurrency.
     *
     * @param string $ip
     * @return void
     */
    public static function updateFailedAttempts($ip)
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
    public static function isBlacklisted($ip)
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
