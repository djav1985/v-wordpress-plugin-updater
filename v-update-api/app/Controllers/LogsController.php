<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: LogsController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\ErrorManager;
use App\Models\LogModel;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;
use App\Core\ResponseManager;

class LogsController
{
    public function __construct(private LogModel $logModel)
    {
    }

    /**
     * Handles GET requests for the logs page.
     */
    public function handleRequest(): ResponseManager
    {
        $ploutput = $this->logModel->getLogs('plugin');
        $thoutput = $this->logModel->getLogs('theme');

        return ResponseManager::view('logs', [
            'ploutput' => $ploutput,
            'thoutput' => $thoutput,
        ]);
    }

    /**
     * Handles POST submissions on the logs page.
     */
    public function handleSubmission(): ResponseManager
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return ResponseManager::redirect('/logs');
        }

        if (isset($_POST['clear_logs'])) {
            $this->logModel->clearAllLogs();
            MessageHelper::addMessage('Logs cleared successfully.');
        }
        return ResponseManager::redirect('/logs');
    }
}
