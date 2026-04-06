<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
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

        $conn = (new DatabaseManager())->getConnection();

        self::syncPluginsDir(PLUGINS_DIR, $conn);
        self::syncThemesDir(THEMES_DIR, $conn);
        self::cleanupBlacklist($conn);

        echo "Cron job completed successfully.\n";
    }

    /**
     * Sync ZIP artifacts in plugins directory into plugins table.
     */
    private static function syncPluginsDir(string $dir, Connection $conn): void
    {
        self::syncDir(
            $dir,
            $conn,
            'INSERT INTO plugins (slug, version) VALUES (?, ?) ' .
            'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
            'SELECT slug FROM plugins',
            'DELETE FROM plugins WHERE slug = ?'
        );
    }

    /**
     * Sync ZIP artifacts in themes directory into themes table.
     */
    private static function syncThemesDir(string $dir, Connection $conn): void
    {
        self::syncDir(
            $dir,
            $conn,
            'INSERT INTO themes (slug, version) VALUES (?, ?) ' .
            'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
            'SELECT slug FROM themes',
            'DELETE FROM themes WHERE slug = ?'
        );
    }

    /**
     * Shared ZIP directory synchronization logic using explicit SQL statements.
     */
    private static function syncDir(
        string $dir,
        Connection $conn,
        string $upsertSql,
        string $selectSlugsSql,
        string $deleteSlugSql
    ): void {
        if (!is_dir($dir) || !is_readable($dir)) {
            error_log(sprintf('CronHelper::syncDir cannot read directory "%s".', $dir));
            return;
        }

        $files = glob($dir . '/*.zip');
        if ($files === false) {
            error_log(sprintf('CronHelper::syncDir glob() failed for directory "%s".', $dir));
            return;
        }

        $found = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (preg_match('/^(.+)_([\d\.]+)\.zip$/', $name, $matches)) {
                $slug = $matches[1];
                $version = $matches[2];
                $found[$slug] = true;
                $conn->executeStatement($upsertSql, [$slug, $version]);
            }
        }

        $rows = $conn->fetchAllAssociative($selectSlugsSql);
        foreach ($rows as $row) {
            if (!isset($found[$row['slug']])) {
                $conn->executeStatement($deleteSlugSql, [$row['slug']]);
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
