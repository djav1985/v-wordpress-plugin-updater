<?php

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

use App\Core\Utility;
use App\Core\ErrorMiddleware;

class PluginsController
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        } elseif (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            if (isset($_FILES['plugin_file'])) {
                self::uploadPluginFiles();
            } elseif (isset($_POST['delete_plugin'])) {
                $plugin_name = isset($_POST['plugin_name'])
                    ? Utility::validateSlug($_POST['plugin_name'])
                    : null;
                self::deletePlugin($plugin_name);
            }
        } else {
            $error = 'Invalid Form Action.';
            ErrorMiddleware::logMessage($error);
            $_SESSION['messages'][] = $error;
            header('Location: /');
            exit();
        }
    }

    /**
     * Uploads plugin files to the server.
     *
     * Validates file extensions, removes existing plugins with the same slug, and moves the uploaded files.
     *
     * @return void
     */
    private static function uploadPluginFiles(): void
    {
        $allowed_extensions = ['zip'];
        $total_files = count($_FILES['plugin_file']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($_FILES['plugin_file']['name'][$i])
            ? Utility::validateFilename($_FILES['plugin_file']['name'][$i])
            : '';
            $file_tmp = isset($_FILES['plugin_file']['tmp_name'][$i])
            ? $_FILES['plugin_file']['tmp_name'][$i]
            : '';
            $file_error = isset($_FILES['plugin_file']['error'][$i])
            ? filter_var($_FILES['plugin_file']['error'][$i], FILTER_VALIDATE_INT)
            : UPLOAD_ERR_NO_FILE;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $plugin_slug = explode('_', $file_name)[0];
            $existing_plugins = glob(PLUGINS_DIR . '/' . $plugin_slug . '_*');
            foreach ($existing_plugins as $plugin) {
                if (is_file($plugin)) {
                    unlink($plugin);
                }
            }

            if ($file_error !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
                $error = 'Error uploading: ' .
                    htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') .
                    '. Only .zip files are allowed.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
                continue;
            }

            $plugin_path = PLUGINS_DIR . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $plugin_path)) {
                $_SESSION['messages'][] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
            } else {
                $error = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
        }

        header('Location: /plupdate');
        exit();
    }

    /**
     * Deletes a plugin file from the server.
     *
     * Validates the plugin name and ensures the file exists before deletion.
     *
     * @param string|null $plugin_name The name of the plugin to delete.
     *
     * @return void
     */
    private static function deletePlugin(?string $plugin_name): void
    {
        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $error = 'Invalid CSRF token.';
            ErrorMiddleware::logMessage($error);
            $_SESSION['messages'][] = $error;
            header('Location: /plupdate');
            exit();
        }

        $plugin_name = Utility::validateFilename($plugin_name);
        $plugin_name = basename((string) $plugin_name);
        $plugin_path = PLUGINS_DIR . '/' . $plugin_name;
        if (
            file_exists($plugin_path) &&
            dirname(realpath($plugin_path)) === realpath(PLUGINS_DIR)
        ) {
            if (unlink($plugin_path)) {
                $_SESSION['messages'][] = 'Plugin deleted successfully!';
            } else {
                $error = 'Failed to delete plugin file. Please try again.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
            header('Location: /plupdate');
            exit();
        }
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
        $plugins = glob(PLUGINS_DIR . "/*.zip");
        $plugins = array_reverse($plugins);
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
