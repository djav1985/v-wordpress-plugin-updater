<?php
// phpcs:ignoreFile PSR1.Files.SideEffects

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\ErrorManager;
use App\Helpers\WorkerHelper;

const JOB_NAME = 'v-updater-cron';

// Parse command line arguments
$options = getopt('', ['worker']);
$isWorker = isset($options['worker']);

// Run as background worker if --worker flag is provided
if ($isWorker) {
    if (!WorkerHelper::canLaunch(JOB_NAME)) {
        exit(0);
    }
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' > /dev/null 2>&1 &';
    exec($cmd);
    exit(0);
}

ErrorManager::handle(function () use ($isWorker): void {
    $lock = WorkerHelper::claimLock(JOB_NAME);
    if ($lock === null) {
        if (!$isWorker) {
            echo "Another cron job is already running. Exiting.\n";
        }
        return;
    }

    $release = static function () use ($lock): void {
        WorkerHelper::releaseLock($lock);
    };

    register_shutdown_function($release);

    try {
        runCronJob($isWorker);
    } finally {
        $release();
    }
});

function runCronJob(bool $isWorker): void
{
    $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';
    require __DIR__ . '/config.php';
    
    $conn = \App\Core\DatabaseManager::getConnection();
    
    // Sync plugins and themes
    syncDir(PLUGINS_DIR, 'plugins', $conn);
    syncDir(THEMES_DIR, 'themes', $conn);
    
    // Clean up blacklist: remove blocked IPs after 7 days, unblocked after 3 days
    cleanupBlacklist($conn);
    
    if (!$isWorker) {
        echo "Cron job completed successfully.\n";
    }
}

function syncDir(string $dir, string $table, \Doctrine\DBAL\Connection $conn): void
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

function cleanupBlacklist(\Doctrine\DBAL\Connection $conn): void
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
