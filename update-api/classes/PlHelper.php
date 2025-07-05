<?php

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: PlHelper.php
 * Description: WordPress Update API Helper for plugin updates
 */


class PlHelper
{
    public static function handleRequest(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            if (isset($_FILES['plugin_file'])) {
                self::uploadPluginFiles();
            } elseif (isset($_POST['delete_plugin'])) {
                $plugin_name = isset($_POST['plugin_name']) ? UtilityHandler::validateSlug($_POST['plugin_name']) : null;
                self::deletePlugin($plugin_name);
            }
        } else {
            $error = 'Invalid Form Action.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
            header('Location: /');
            exit();
        }
    }

    private static function uploadPluginFiles(): void
    {
        $allowed_extensions = ['zip'];
        $total_files = count($_FILES['plugin_file']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($_FILES['plugin_file']['name'][$i])
            ? UtilityHandler::validateFilename($_FILES['plugin_file']['name'][$i])
            : '';
            $file_tmp = isset($_FILES['plugin_file']['tmp_name'][$i])
            ? $_FILES['plugin_file']['tmp_name'][$i]
            : '';
            $file_size = isset($_FILES['plugin_file']['size'][$i])
            ? filter_var($_FILES['plugin_file']['size'][$i], FILTER_VALIDATE_INT)
            : 0;
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
                $error = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . '. Only .zip files are allowed.';
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
                continue;
            }

            $plugin_path = PLUGINS_DIR . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $plugin_path)) {
                $_SESSION['messages'][] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
            } else {
                $error = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
        }

        header('Location: /plupdate');
        exit();
    }

    private static function deletePlugin(?string $plugin_name): void
    {
        $plugin_name = UtilityHandler::validateFilename($plugin_name);
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
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
            header('Location: /plupdate');
            exit();
        }
    }

    public static function generatePluginTableRow(string $plugin, string $pluginName): string
    {
        return '<tr>
            <td>' . htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8') . '</td>
            <td>
                <form class="delete-plugin-form" action="/plupdate" method="POST">
                    <input type="hidden" name="plugin_name" value="' .
                    htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8') .
                '">
                    <button class="pl-submit" type="submit" name="delete_plugin">Delete</button>
                </form>
            </td>
        </tr>';
    }

    /**
     * Generates the plugins table HTML for display.
     *
     * @return string
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
                $pluginsTableHtml .= self::generatePluginTableRow($plugin, $pluginName);
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
                $pluginsTableHtml .= self::generatePluginTableRow($plugin, $pluginName);
            }

            $pluginsTableHtml .= '</tbody></table></div></div>';
        } else {
            $pluginsTableHtml = "No plugins found.";
        }
        return $pluginsTableHtml;
    }
}
