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
use App\Core\Response;

class LogsController
{
    /**
     * Handles GET requests for the logs page.
     *
     * @return Response
     */
    public function handleRequest(): Response
    {
        $ploutput = LogModel::getLogs('plugin');
        $thoutput = LogModel::getLogs('theme');

        return Response::view('logs', [
            'ploutput' => $ploutput,
            'thoutput' => $thoutput,
        ]);
    }

    /**
     * Handles POST submissions on the logs page.
     *
     * @return Response
     */
    public function handleSubmission(): Response
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return Response::redirect('/logs');
        }

        if (isset($_POST['clear_logs'])) {
            LogModel::clearAllLogs();
            MessageHelper::addMessage('Logs cleared successfully.');
        }
        return Response::redirect('/logs');
    }
}
