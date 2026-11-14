<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: PluginsController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Helpers\ValidationHelper;
use App\Core\ErrorManager;
use App\Models\PluginModel;
use App\Helpers\MessageHelper;
use App\Core\Response;
use App\Helpers\RequestInput;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class PluginsController
{
    /**
     * Handles GET requests for plugin-related actions.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        return Response::view('plupdate', [
            'plugins' => PluginModel::getPlugins(),
        ]);
    }

    /**
     * Handles POST submissions for plugin-related actions.
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
            $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
            if ($isAjax) {
                return Response::text($error, 400);
            }
            MessageHelper::addMessage($error);
            return Response::redirect('/plupdate');
        }

        $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
        $uploadedFiles = $request->getUploadedFiles();
        if (isset($uploadedFiles['plugin_file'])) {
            $upload = $uploadedFiles['plugin_file'];
            if (!is_array($upload) && !$upload instanceof UploadedFileInterface) {
                return Response::text('Invalid upload payload.', 400);
            }
            $messages = PluginModel::uploadPsrFiles($upload, $isAjax);
            if ($isAjax) {
                return Response::text(implode("\n", $messages));
            }
            foreach ($messages as $message) {
                MessageHelper::addMessage($message);
            }
            return Response::redirect('/plupdate');
        } elseif (isset($post['delete_plugin'])) {
            $pluginName = ValidationHelper::validateFilename(
                RequestInput::string($post, 'plugin_name') ?? ''
            );
            if ($pluginName !== null && PluginModel::deletePlugin($pluginName)) {
                MessageHelper::addMessage('Plugin deleted successfully!');
            } else {
                $error = 'Failed to delete plugin file. Please try again.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
            return Response::redirect('/plupdate');
        }
        return Response::redirect('/plupdate');
    }

}
