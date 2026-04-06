<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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
use App\Core\ResponseManager;

class PluginsController
{
    /**
     * Handles GET requests for plugin-related actions.
     */
    public function handleRequest(): ResponseManager
    {
        $pluginsTableHtml = $this->getPluginsTableHtml();
        return ResponseManager::view('plupdate', [
            'pluginsTableHtml' => $pluginsTableHtml,
        ]);
    }

    /**
     * Handles POST submissions for plugin-related actions.
     */
    public function handleSubmission(): ResponseManager
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::log($error);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                return ResponseManager::text($error, 400);
            }
            MessageHelper::addMessage($error);
            return ResponseManager::redirect('/plupdate');
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if (isset($_FILES['plugin_file'])) {
            $messages = PluginModel::uploadFiles($_FILES['plugin_file'], $isAjax);
            if ($isAjax) {
                return ResponseManager::text(implode("\n", $messages));
            }
            foreach ($messages as $message) {
                MessageHelper::addMessage($message);
            }
            return ResponseManager::redirect('/plupdate');
        } elseif (isset($_POST['delete_plugin'])) {
            $pluginName = isset($_POST['plugin_name'])
                ? ValidationHelper::validateSlug($_POST['plugin_name'])
                : null;
            if ($pluginName !== null && PluginModel::deletePlugin($pluginName)) {
                MessageHelper::addMessage('Plugin deleted successfully!');
            } else {
                $error = 'Failed to delete plugin file. Please try again.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
            return ResponseManager::redirect('/plupdate');
        }
        return ResponseManager::redirect('/plupdate');
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
        $csrfToken = \App\Core\SessionManager::getInstance()->get('csrf_token') ?? '';
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
