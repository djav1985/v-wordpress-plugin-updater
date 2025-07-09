<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: HostsModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

class HostsModel
{
    public static string $file = HOSTS_ACL . '/HOSTS';

    /**
     * Return all host file entries as array of lines.
     *
     * @return array
     */
    public static function getEntries(): array
    {
        return file_exists(self::$file) ? file(self::$file, FILE_IGNORE_NEW_LINES) : [];
    }

    /**
     * Add an entry to the hosts file.
     *
     * @param string $domain
     * @param string $key
     *
     * @return bool
     */
    public static function addEntry(string $domain, string $key): bool
    {
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $new_entry = $safe_domain . ' ' . $safe_key;
        return file_put_contents(self::$file, $new_entry . "\n", FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Update an entry in the hosts file.
     *
     * @param int    $line
     * @param string $domain
     * @param string $key
     *
     * @return bool
     */
    public static function updateEntry(int $line, string $domain, string $key): bool
    {
        $entries = self::getEntries();
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $entries[$line] = $safe_domain . ' ' . $safe_key;
        return file_put_contents(self::$file, implode("\n", $entries) . "\n") !== false;
    }

    /**
     * Delete an entry from the hosts file.
     *
     * @param int    $line
     * @param string $domain
     *
     * @return bool
     */
    public static function deleteEntry(int $line, string $domain): bool
    {
        $entries = self::getEntries();
        unset($entries[$line]);
        $result = file_put_contents(self::$file, implode("\n", $entries) . "\n") !== false;

        // also remove from log files
        $log_files = [
            'plugin.log',
            'theme.log',
        ];
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        foreach ($log_files as $log_file) {
            $log_file_path = LOG_DIR . "/$log_file";
            if (file_exists($log_file_path)) {
                $log_entries = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $filtered_entries = array_filter($log_entries, function ($entry) use ($safe_domain) {
                    return strpos($entry, $safe_domain) !== 0 ? true : false;
                });
                file_put_contents($log_file_path, implode("\n", $filtered_entries) . "\n");
            }
        }

        return $result;
    }
}
