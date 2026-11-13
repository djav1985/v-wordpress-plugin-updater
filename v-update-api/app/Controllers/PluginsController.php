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

use App\Core\SessionManager;
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
        $pluginsTableHtml = $this->getPluginsTableHtml();
        return Response::view('plupdate', [
            'pluginsTableHtml' => $pluginsTableHtml,
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

    /**
     * Generates an HTML table row for a plugin.
     * @param array{slug: string, version: string} $pluginName
     */
    private function generatePluginTableRow(array $pluginName): string
    {
        $name = str_replace(['-', '_'], ' ', $pluginName['slug']);
        $version = $pluginName['version'];
        $pluginFile = $pluginName['slug'] . '_' . $version . '.zip';
        $csrfToken = SessionManager::getInstance()->get('csrf_token') ?? '';
        return '<tr>
            <td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</td>
            <td>
                <form method="POST" action="/plupdate" class="inline-action-form">
                    <input type="hidden" name="csrf_token" value="' .
                        htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="plugin_name" value="' .
                        htmlspecialchars($pluginFile, ENT_QUOTES, 'UTF-8') . '">
                    <button class="pl-submit red-button" type="submit" name="delete_plugin">Delete</button>
                </form>
            </td>
        </tr>';
    }

    /**
     * Generates the plugins table HTML for display.
     *
     * @return string HTML table or message.
     */
    private function getPluginsTableHtml(): string
    {
        $plugins = PluginModel::getPlugins();
        if (count($plugins) > 0) {
            $halfCount = (int) ceil(count($plugins) / 2);
            $pluginsColumn1 = array_slice($plugins, 0, $halfCount);
            $pluginsColumn2 = array_slice($plugins, $halfCount);
            $pluginsTableHtml = '<div class="row"><div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Version</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($pluginsColumn1 as $plugin) {
                $pluginsTableHtml .= $this->generatePluginTableRow($plugin);
            }

            $pluginsTableHtml .= '</tbody></table></div><div class="column"><table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Version</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($pluginsColumn2 as $plugin) {
                $pluginsTableHtml .= $this->generatePluginTableRow($plugin);
            }

            $pluginsTableHtml .= '</tbody></table></div></div>';
        } else {
            $pluginsTableHtml = "No plugins found.";
        }
        return $pluginsTableHtml;
    }
}
