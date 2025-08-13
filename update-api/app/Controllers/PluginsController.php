<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: PluginsController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Helpers\Validation;
use App\Core\ErrorManager;
use App\Core\Controller;
use App\Models\PluginModel;
use App\Helpers\MessageHelper;

class PluginsController extends Controller
{
    /**
     * Handles the incoming request for plugin-related actions.
     *
     * Validates CSRF tokens and determines whether to upload or delete plugins.
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
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if (isset($_FILES['plugin_file'])) {
                    $messages = PluginModel::uploadFiles($_FILES['plugin_file'], $isAjax);
                    if ($isAjax) {
                        echo implode("\n", $messages);
                        exit();
                    }
                    foreach ($messages as $message) {
                        MessageHelper::addMessage($message);
                    }
                    header('Location: /plupdate');
                    exit();
                } elseif (isset($_POST['delete_plugin'])) {
                    $plugin_name = isset($_POST['plugin_name'])
                        ? Validation::validateSlug($_POST['plugin_name'])
                        : null;
                    if ($plugin_name !== null && PluginModel::deletePlugin($plugin_name)) {
                        MessageHelper::addMessage('Plugin deleted successfully!');
                    } else {
                        $error = 'Failed to delete plugin file. Please try again.';
                        ErrorManager::getInstance()->log($error);
                        MessageHelper::addMessage($error);
                    }
                    header('Location: /plupdate');
                    exit();
                }
            } else {
                $error = 'Invalid Form Action.';
                ErrorManager::getInstance()->log($error);
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    http_response_code(400);
                    echo $error;
                    exit();
                }
                MessageHelper::addMessage($error);
                header('Location: /');
                exit();
            }
        }

        $pluginsTableHtml = self::getPluginsTableHtml();

        // Render the plupdate view
        (new self())->render('plupdate', [
            'pluginsTableHtml' => $pluginsTableHtml,
        ]);
    }


    /**
     * Generates an HTML table row for a plugin.
     *
     * @param string $pluginName The name of the plugin.
     *
     * @return string The HTML table row for the plugin.
     */
    public static function generatePluginTableRow(string $pluginName): string
    {
        return '<tr>
            <td>' . htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8') . '</td>
            <td>
                <form class="delete-plugin-form" action="/plupdate" method="POST">
                    <input type="hidden" name="plugin_name" value="' .
                    htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8') .
                '">
                    <input type="hidden" name="csrf_token" value="' .
                    htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">
                    <button class="pl-submit" type="submit" name="delete_plugin">Delete</button>
                </form>
            </td>
        </tr>';
    }

    /**
     * Generates the plugins table HTML for display.
     *
     * Retrieves all plugin files and organizes them into two columns for display.
     *
     * @return string The HTML for the plugins table.
     */
    public static function getPluginsTableHtml(): string
    {
        $plugins = PluginModel::getPlugins();
        if (count($plugins) > 0) {
            $halfCount = ceil(count($plugins) / 2);
            $pluginsColumn1 = array_slice($plugins, 0, $halfCount);
            $pluginsColumn2 = array_slice($plugins, $halfCount);
            $pluginsTableHtml = '<div class="row"><div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Plugin Name</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($pluginsColumn1 as $plugin) {
                $pluginName = basename($plugin);
                $pluginsTableHtml .= self::generatePluginTableRow($pluginName);
            }

            $pluginsTableHtml .= '</tbody></table></div><div class="column"><table>
                <thead>
                    <tr>
                        <th>Plugin Name</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($pluginsColumn2 as $plugin) {
                $pluginName = basename($plugin);
                $pluginsTableHtml .= self::generatePluginTableRow($pluginName);
            }

            $pluginsTableHtml .= '</tbody></table></div></div>';
        } else {
            $pluginsTableHtml = "No plugins found.";
        }
        return $pluginsTableHtml;
    }
}
