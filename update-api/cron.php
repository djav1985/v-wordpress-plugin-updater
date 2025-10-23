<?php
// phpcs:ignoreFile PSR1.Files.SideEffects

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\DatabaseManager;
use App\Core\ErrorManager;
use App\Helpers\WorkerHelper;
use App\Helpers\CronWorker;

const JOB_NAME = 'v-updater-cron';

/**
 * @return never
 */
function printUsage(): void
{
    echo "Usage:\n";
    echo "  php cron.php\n";
    echo "  php cron.php worker\n";
    exit(1);
}

// Parse command line arguments
$options = getopt('', ['worker']);
$isWorker = isset($options['worker']);

// Derive raw args (excluding script name) and show usage when none provided
$args = $GLOBALS['argv'] ?? [];
if (empty($args)) {
        printUsage();
}

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
    
    $conn = DatabaseManager::getConnection();
    
    // Sync plugins and themes
    CronWorker::syncDir(PLUGINS_DIR, 'plugins', $conn);
    CronWorker::syncDir(THEMES_DIR, 'themes', $conn);
    
    // Clean up blacklist: remove blocked IPs after 7 days, unblocked after 3 days
    CronWorker::cleanupBlacklist($conn);
    
    if (!$isWorker) {
        echo "Cron job completed successfully.\n";
    }
}
