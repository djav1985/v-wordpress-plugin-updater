<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: LogsController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ErrorManager;
use App\Models\LogModel;
use App\Helpers\MessageHelper;
use App\Core\Csrf;

class LogsController extends Controller
{
    /**
     * Handles GET requests for the logs page.
     */
    public function handleRequest(): void
    {
        $ploutput = LogModel::processLogFile('plugin.log');
        $thoutput = LogModel::processLogFile('theme.log');

        $this->render('logs', [
            'ploutput' => $ploutput,
            'thoutput' => $thoutput,
        ]);
    }

    /**
     * Handles POST submissions on the logs page.
     */
    public function handleSubmission(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
            header('Location: /logs');
            exit();
        }

        if (isset($_POST['clear_logs'])) {
            LogModel::clearAllLogs();
            MessageHelper::addMessage('Logs cleared successfully.');
        }
        header('Location: /logs');
        exit();
    }
}
