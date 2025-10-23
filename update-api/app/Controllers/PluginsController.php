<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
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
use App\Core\Csrf;
use App\Core\SessionManager;

class PluginsController extends Controller
{
    /**
     * Handles GET requests for plugin-related actions.
     */
    public function handleRequest(): void
    {
        $pluginsTableHtml = self::getPluginsTableHtml();
        $hosts = \App\Models\HostsModel::getHosts();
        $this->render('plupdate', [
            'pluginsTableHtml' => $pluginsTableHtml,
            'hosts' => $hosts,
        ]);
    }

    /**
     * Handles POST submissions for plugin-related actions.
     */
    public function handleSubmission(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
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
        } elseif (isset($_POST['install_plugin'])) {
            $plugin_name = isset($_POST['plugin_name'])
                ? Validation::validateSlug($_POST['plugin_name'])
                : null;
            $domain = isset($_POST['domain']) ? $_POST['domain'] : null;
            
            if ($plugin_name === null || $domain === null) {
                $error = 'Invalid plugin name or domain.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
                header('Location: /plupdate');
                exit();
            }
            
            $result = self::installPluginToDomain($plugin_name, $domain);
            MessageHelper::addMessage($result['message']);
            header('Location: /plupdate');
            exit();
        }
    }

    /**
     * Install a plugin to a specific domain via REST API.
     *
     * @param string $plugin_name The plugin slug_version
     * @param string $domain The target domain
     * @return array{success: bool, message: string}
     */
    private static function installPluginToDomain(string $plugin_name, string $domain): array
    {
        $plugin_path = PluginModel::$dir . '/' . basename($plugin_name);
        
        if (!file_exists($plugin_path)) {
            return ['success' => false, 'message' => 'Plugin file not found.'];
        }
        
        // Get the API key for the domain
        $conn = \App\Core\DatabaseManager::getConnection();
        $key_encrypted = $conn->fetchOne('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        
        if (!$key_encrypted) {
            return ['success' => false, 'message' => 'Domain not found in hosts table.'];
        }
        
        $key = \App\Helpers\Encryption::decrypt($key_encrypted);
        
        // Prepare the API request
        $url = 'https://' . $domain . '/wp-json/vwpd/v1/plugins';
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $key,
            ],
            CURLOPT_POSTFIELDS => [
                'package' => new \CURLFile($plugin_path, 'application/zip', basename($plugin_name)),
            ],
            CURLOPT_TIMEOUT => 300,
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code === 200) {
            return ['success' => true, 'message' => 'Plugin installed successfully to ' . $domain];
        } else {
            $error_msg = $response ?: 'Failed to install plugin';
            return ['success' => false, 'message' => 'Failed to install plugin to ' . $domain . ': ' . $error_msg];
        }
    }

    /**
     * Generates an HTML table row for a plugin.
     * @param array{slug: string, version: string} $pluginName
     */
    private static function generatePluginTableRow(array $pluginName): string
    {
        $name = str_replace(['-', '_'], ' ', $pluginName['slug']);
        $version = $pluginName['version'];
        $plugin_file = $pluginName['slug'] . '_' . $version . '.zip';
        return '<tr>
            <td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</td>
            <td>
                <button class="pl-submit action-btn" type="button" onclick="openPluginActionModal(\'' .
                    htmlspecialchars($plugin_file, ENT_QUOTES, 'UTF-8') . '\', \'' .
                    htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '\')">Action</button>
            </td>
        </tr>';
    }

    /**
     * Generates the plugins table HTML for display.
     */
    private static function getPluginsTableHtml(): string
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
                $pluginsTableHtml .= self::generatePluginTableRow($plugin);
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
                $pluginsTableHtml .= self::generatePluginTableRow($plugin);
            }

            $pluginsTableHtml .= '</tbody></table></div></div>';
        } else {
            $pluginsTableHtml = "No plugins found.";
        }
        return $pluginsTableHtml;
    }
}
