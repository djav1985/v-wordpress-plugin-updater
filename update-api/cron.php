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

$rawArgs = $GLOBALS['argv'] ?? [];
array_shift($rawArgs);

if (!empty($rawArgs)) {
    $usage = "Usage:
  php cron.php
";
    $msg = "Unrecognized argument: " . implode(' ', $rawArgs) . "

" . $usage;
    if (defined('STDERR')) {
        fwrite(STDERR, $msg);
    } else {
        echo $msg;
    }
    exit(1);
}

ErrorManager::handle(function (): void {
    $lock = WorkerHelper::claimLock(JOB_NAME);
    if ($lock === null) {
        echo "Another cron job is already running. Exiting.
";
        return;
    }

    $release = static function () use ($lock): void {
        WorkerHelper::releaseLock($lock);
    };

    register_shutdown_function($release);

    try {
        runCronJob();
    } finally {
        $release();
    }
});

/**
 * Execute the main cron job: sync plugins/themes directories and clean up the blacklist.
 */
function runCronJob(): void
{
    $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';
    require __DIR__ . '/config.php';

    $conn = DatabaseManager::getConnection();

    CronHelper::syncDir(PLUGINS_DIR, 'plugins', $conn);
    CronHelper::syncDir(THEMES_DIR, 'themes', $conn);
    CronHelper::cleanupBlacklist($conn);

    echo "Cron job completed successfully.
";
}
