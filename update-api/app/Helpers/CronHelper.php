<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: CronHelper.php
 * Description: WordPress Update API
 */

namespace App\Helpers;

use App\Core\DatabaseManager;
use Doctrine\DBAL\Connection;

/**
 * Helper utilities for cron synchronization and housekeeping tasks.
 */
class CronHelper
{
    /**
     * Execute the main cron job: sync plugins/themes directories and clean up the blacklist.
     */
    public static function runCronJob(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2) . '/public';
        require dirname(__DIR__, 2) . '/config.php';

        $conn = DatabaseManager::getConnection();

        self::syncDir(PLUGINS_DIR, 'plugins', $conn);
        self::syncDir(THEMES_DIR, 'themes', $conn);
        self::cleanupBlacklist($conn);

        echo "Cron job completed successfully.\n";
    }

    /**
     * Sync ZIP artifacts in a directory into the given table, keeping only discovered slugs.
     */
    private static function syncDir(string $dir, string $table, Connection $conn): void
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            error_log(sprintf('CronHelper::syncDir cannot read directory "%s" for table "%s".', $dir, $table));
            return;
        }

        $files = glob($dir . '/*.zip');
        if ($files === false) {
            error_log(sprintf('CronHelper::syncDir glob() failed for directory "%s" and table "%s".', $dir, $table));
            return;
        }

        $found = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (preg_match('/^(.+)_([\d\.]+)\.zip$/', $name, $matches)) {
                $slug = $matches[1];
                $version = $matches[2];
                $found[$slug] = true;
                $conn->executeStatement(
                    "INSERT INTO $table (slug, version) VALUES (?, ?) " .
                    "ON CONFLICT(slug) DO UPDATE SET version = excluded.version",
                    [$slug, $version]
                );
            }
        }
        $rows = $conn->fetchAllAssociative("SELECT slug FROM $table");
        foreach ($rows as $row) {
            if (!isset($found[$row['slug']])) {
                $conn->executeStatement("DELETE FROM $table WHERE slug = ?", [$row['slug']]);
            }
        }
    }

    /**
     * Cleanup expired blacklist entries.
     */
    private static function cleanupBlacklist(Connection $conn): void
    {
        $currentTime = time();
        $sevenDaysAgo = $currentTime - (7 * 24 * 60 * 60);
        $threeDaysAgo = $currentTime - (3 * 24 * 60 * 60);

        // Remove IPs that were blocked more than 7 days ago
        $conn->executeStatement(
            'DELETE FROM blacklist WHERE blacklisted = 1 AND timestamp < ?',
            [$sevenDaysAgo]
        );

        // Remove IPs that are not blocked and haven't been updated in 3 days
        $conn->executeStatement(
            'DELETE FROM blacklist WHERE blacklisted = 0 AND timestamp < ?',
            [$threeDaysAgo]
        );
    }
}
