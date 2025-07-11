<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: PluginModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\Utility;

class PluginModel
{
    public static string $dir = PLUGINS_DIR;

    /**
     * Return array of plugin file paths.
     *
     * @return array
     */
    public static function getPlugins(): array
    {
        $plugins = glob(self::$dir . '/*.zip');
        return array_reverse($plugins ?: []);
    }

    /**
     * Delete a plugin file.
     *
     * @param string $plugin_name
     *
     * @return bool True on success, false otherwise.
     */
    public static function deletePlugin(string $plugin_name): bool
    {
        $plugin_path = self::$dir . '/' . basename($plugin_name);
        if (
            file_exists($plugin_path) &&
            dirname(realpath($plugin_path)) === realpath(self::$dir)
        ) {
            return unlink($plugin_path);
        }

        return false;
    }

    /**
     * Upload plugin files.
     *
     * @param array $fileArray $_FILES['plugin_file'] array structure
     * @param bool  $isAjax    Whether the request was via AJAX
     *
     * @return array Array of status messages
     */
    public static function uploadFiles(array $fileArray, bool $isAjax = false): array
    {
        $messages = [];
        $allowed_extensions = ['zip'];
        $total_files = count($fileArray['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($fileArray['name'][$i]) ? Utility::validateFilename($fileArray['name'][$i]) : '';
            $file_tmp = isset($fileArray['tmp_name'][$i]) ? $fileArray['tmp_name'][$i] : '';
            $file_error = isset($fileArray['error'][$i]) ? filter_var($fileArray['error'][$i], FILTER_VALIDATE_INT) : UPLOAD_ERR_NO_FILE;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $plugin_slug = explode('_', $file_name)[0];
            $existing_plugins = glob(self::$dir . '/' . $plugin_slug . '_*');
            foreach ($existing_plugins as $plugin) {
                if (is_file($plugin)) {
                    unlink($plugin);
                }
            }

            $max_upload_size = min(
                self::_parseIniSize(ini_get('upload_max_filesize')),
                self::_parseIniSize(ini_get('post_max_size'))
            );

            if ($fileArray['size'][$i] > $max_upload_size) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') .
                    '. File size exceeds the maximum allowed size of ' . ($max_upload_size / (1024 * 1024)) . ' MB.';
                continue;
            }

            if ($file_error !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') .
                    '. Only .zip files are allowed, and filenames must follow the format: plugin-name_1.0.zip';
                continue;
            }

            $plugin_path = self::$dir . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $plugin_path)) {
                $messages[] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
            } else {
                $messages[] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
            }
        }

        return $messages;
    }

    /**
     * Parse a size string from php.ini into bytes.
     *
     * @param string $size The size string (e.g., '64M', '128K').
     *
     * @return int The size in bytes.
     */
    private static function _parseIniSize(string $size): int
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int)$size;

        switch ($unit) {
        case 'K':
            return $value * 1024;
        case 'M':
            return $value * 1024 * 1024;
        case 'G':
            return $value * 1024 * 1024 * 1024;
        default:
            return $value;
        }
    }
}
