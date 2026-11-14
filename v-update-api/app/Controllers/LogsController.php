<?php

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
use App\Helpers\RequestInput;
use Psr\Http\Message\ServerRequestInterface;

class LogsController
{
    /**
     * Handles GET requests for the logs page.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $pluginLogs = LogModel::getLogs('plugin');
        $themeLogs = LogModel::getLogs('theme');

        return Response::view('logs', [
            'pluginLogs' => $pluginLogs,
            'themeLogs' => $themeLogs,
        ]);
    }

    /**
     * Handles POST submissions on the logs page.
     *
    * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $post = $request->getParsedBody();
        if (!is_array($post)) {
            return new Response(400);
        }
        $token = RequestInput::string($post, 'csrf_token') ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return Response::redirect('/logs');
        }

        if (isset($post['clear_logs'])) {
            LogModel::clearAllLogs();
            MessageHelper::addMessage('Logs cleared successfully.');
        }
        return Response::redirect('/logs');
    }
}
