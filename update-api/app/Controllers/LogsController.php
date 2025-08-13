<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: LogsController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ErrorManager;
use App\Models\LogModel;
use App\Helpers\MessageHelper;

class LogsController extends Controller
{
    /**
     * Handles the request for the logs page.
     *
     * Generates log output for plugins and themes and includes the log view.
     *
     * @return void
     */
    public static function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
                hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
            ) {
                if (isset($_POST['clear_logs'])) {
                    LogModel::clearAllLogs();
                    MessageHelper::addMessage('Logs cleared successfully.');
                }
                header('Location: /logs');
                exit();
            }
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
            header('Location: /logs');
            exit();
        }

        $ploutput = LogModel::processLogFile('plugin.log');
        $thoutput = LogModel::processLogFile('theme.log');

        // Use the render method to include the logs view
        (new self())->render('logs', [
            'ploutput' => $ploutput,
            'thoutput' => $thoutput,
        ]);
    }

}
