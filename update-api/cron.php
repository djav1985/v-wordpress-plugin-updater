<?php
// phpcs:ignoreFile PSR1.Files.SideEffects

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: cron.php
 * Description: WordPress Update API
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\DatabaseManager;
use App\Core\ErrorManager;
use App\Helpers\WorkerHelper;
use App\Helpers\CronHelper;

const JOB_NAME = 'v-updater-cron';

/**
 * Print CLI usage instructions and exit with code 1.
 *
 * @param string $errorMessage Optional error message to prepend to usage text.
 * @return never
 */
function printUsage(string $errorMessage = ''): never
{
    $usage = "Usage:\n"
        . "  php cron.php\n"
        . "  php cron.php --worker\n"
        . "  php cron.php worker\n";

    if ($errorMessage !== '') {
        $usage = $errorMessage . "\n\n" . $usage;
    }

    if (PHP_SAPI === 'cli' && defined('STDERR')) {
        fwrite(STDERR, $usage . "\n");
    } else {
        echo $usage . "\n";
    }

    exit(1);
}

$rawArgs = $GLOBALS['argv'] ?? [];
array_shift($rawArgs); // remove script name

$isWorker = false;

foreach ($rawArgs as $arg) {
    if ($arg === '--worker' || $arg === 'worker') {
        $isWorker = true;
        continue;
    }

    printUsage("Unrecognized argument: {$arg}");
}

// Run as background worker if worker flag/argument is provided
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

/**
 * Execute the main cron job: sync plugins/themes directories and clean up the blacklist.
 *
 * @param bool $isWorker Whether the script was launched in worker mode (suppresses output).
 * @return void
 */
function runCronJob(bool $isWorker): void
{
    $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';
    require __DIR__ . '/config.php';
    
    $conn = DatabaseManager::getConnection();
    
    // Sync plugins and themes
    CronHelper::syncDir(PLUGINS_DIR, 'plugins', $conn);
    CronHelper::syncDir(THEMES_DIR, 'themes', $conn);
    
    // Clean up blacklist: remove blocked IPs after 7 days, unblocked after 3 days
    CronHelper::cleanupBlacklist($conn);
    
    if (!$isWorker) {
        echo "Cron job completed successfully.\n";
    }
}
