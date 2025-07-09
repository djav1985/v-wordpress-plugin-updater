<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ThemeModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\Utility;

class ThemeModel
{
    public static string $dir = THEMES_DIR;

    /**
     * Return array of theme file paths.
     *
     * @return array
     */
    public static function getThemes(): array
    {
        $themes = glob(self::$dir . '/*.zip');
        return array_reverse($themes ?: []);
    }

    /**
     * Delete a theme file.
     *
     * @param string $theme_name
     *
     * @return bool True on success, false otherwise.
     */
    public static function deleteTheme(string $theme_name): bool
    {
        $theme_path = self::$dir . '/' . basename($theme_name);
        if (
            file_exists($theme_path) &&
            dirname(realpath($theme_path)) === realpath(self::$dir)
        ) {
            return unlink($theme_path);
        }

        return false;
    }

    /**
     * Upload theme files.
     *
     * @param array $fileArray $_FILES['theme_file'] array structure
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

            $theme_slug = explode('_', $file_name)[0];
            $existing_themes = glob(self::$dir . '/' . $theme_slug . '_*');
            foreach ($existing_themes as $theme) {
                if (is_file($theme)) {
                    unlink($theme);
                }
            }

            $max_upload_size = min(
                (int)(ini_get('upload_max_filesize') * 1024 * 1024),
                (int)(ini_get('post_max_size') * 1024 * 1024)
            );

            if ($fileArray['size'][$i] > $max_upload_size) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') .
                    '. File size exceeds the maximum allowed size of ' . ($max_upload_size / (1024 * 1024)) . ' MB.';
                continue;
            }

            if ($file_error !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') .
                    '. Only .zip files are allowed, and filenames must follow the format: theme-name_1.0.zip';
                continue;
            }

            $theme_path = self::$dir . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $theme_path)) {
                $messages[] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
            } else {
                $messages[] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
            }
        }

        return $messages;
    }
}
