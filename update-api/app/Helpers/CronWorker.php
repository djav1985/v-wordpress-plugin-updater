<?php

namespace App\Helpers;

use Doctrine\DBAL\Connection;

/**
 * Helper utilities for cron synchronization and housekeeping tasks.
 */
final class CronWorker
{
    /**
     * Sync ZIP artifacts in a directory into the given table, keeping only discovered slugs.
     */
    public static function syncDir(string $dir, string $table, Connection $conn): void
    {
        $files = glob($dir . '/*.zip');
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
    public static function cleanupBlacklist(Connection $conn): void
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
