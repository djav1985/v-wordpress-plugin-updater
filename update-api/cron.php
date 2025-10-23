<?php
// phpcs:ignoreFile PSR1.Files.SideEffects

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/vendor/autoload.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';
require __DIR__ . '/config.php';

use App\Core\DatabaseManager;

$conn = DatabaseManager::getConnection();

// Parse command line arguments
$options = getopt('', ['worker']);
$isWorker = isset($options['worker']);

// Lock mechanism to ensure only one instance runs at a time
$lockFile = sys_get_temp_dir() . '/v-updater-cron.lock';
$lockHandle = fopen($lockFile, 'c+');

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    if (!$isWorker) {
        echo "Another cron job is already running. Exiting.\n";
    }
    fclose($lockHandle);
    exit(0);
}

// Run as background worker if --worker flag is provided
if ($isWorker) {
    // Detach from terminal for background execution (requires PCNTL extension)
    if (function_exists('pcntl_fork') && function_exists('posix_setsid')) {
        /** @var int|false $pid */
        // @phpstan-ignore-next-line
        /** @psalm-suppress UndefinedFunction */
        $pid = pcntl_fork(); // @intelephense-ignore-line
        if ($pid === -1) {
            echo "Could not fork process\n";
            exit(1);
        } elseif ($pid > 0) {
            // Parent process exits, child continues
            exit(0);
        }
        // Child process becomes session leader
        // @phpstan-ignore-next-line
        /** @psalm-suppress UndefinedFunction */
        if (posix_setsid() === -1) { // @intelephense-ignore-line
            echo "Could not detach from terminal\n";
            exit(1);
        }
    }
}

try {
    // Sync plugins and themes
    syncDir(PLUGINS_DIR, 'plugins', $conn);
    syncDir(THEMES_DIR, 'themes', $conn);

    // Clean up blacklist: remove blocked IPs after 7 days, unblocked after 3 days
    cleanupBlacklist($conn);

    if (!$isWorker) {
        echo "Cron job completed successfully.\n";
    }
} finally {
    // Release the lock
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
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
