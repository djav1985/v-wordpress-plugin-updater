<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: ThemeModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\DatabaseManager;
use App\Helpers\Validation;

class ThemeModel
{
    public static string $dir = THEMES_DIR;

    /**
     * Return array of theme file paths.
     *
     * @return array<int, string>
     */
    public static function getThemes(): array
    {
        $conn = DatabaseManager::getConnection();
        $rows = $conn->fetchAllAssociative('SELECT slug, version FROM themes ORDER BY slug');
        $themes = [];
        foreach ($rows as $row) {
            $themes[] = [
                'slug' => $row['slug'],
                'version' => $row['version'],
            ];
        }
        return $themes;
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
            unlink($theme_path);
            $slug = explode('_', basename($theme_name))[0];
            $conn = DatabaseManager::getConnection();
            $conn->executeStatement('DELETE FROM themes WHERE slug = ?', [$slug]);
            return true;
        }

        return false;
    }

    /**
     * Upload theme files.
     *
     * @param array<string, array<int, mixed>> $fileArray $_FILES['theme_file'] structure
     * @param bool                              $isAjax    Whether the request was via AJAX
     *
     * @return string[] Array of status messages
     */
    public static function uploadFiles(array $fileArray, bool $isAjax = false): array
    {
        $messages = [];
        $allowed_extensions = ['zip'];
        $total_files = count($fileArray['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($fileArray['name'][$i])
                ? Validation::validateFilename($fileArray['name'][$i])
                : '';
            $file_tmp = isset($fileArray['tmp_name'][$i])
                ? $fileArray['tmp_name'][$i]
                : '';
            $file_error = isset($fileArray['error'][$i])
                ? filter_var($fileArray['error'][$i], FILTER_VALIDATE_INT)
                : UPLOAD_ERR_NO_FILE;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $theme_slug = explode('_', $file_name)[0];
            $conn = DatabaseManager::getConnection();
            $current = $conn->fetchOne('SELECT version FROM themes WHERE slug = ?', [$theme_slug]);
            $max_upload_size = min(
                self::_parseIniSize(ini_get('upload_max_filesize')),
                self::_parseIniSize(ini_get('post_max_size'))
            );

            if ($fileArray['size'][$i] > $max_upload_size) {
                $messages[] = 'Error uploading: '
                . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                . '. File size exceeds the maximum allowed size of '
                . ($max_upload_size / (1024 * 1024)) . ' MB.';
                continue;
            }

            if (
                $file_error !== UPLOAD_ERR_OK
                || !in_array($file_extension, $allowed_extensions)
            ) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . '. Only .zip files are allowed, and filenames must follow the format: theme-name_1.0.zip';
                continue;
            }

            if (preg_match('/^(.+)_([\d\.]+)\.zip$/', $file_name, $matches)) {
                $slug = $matches[1];
                $version = $matches[2];
                if ($current && version_compare($version, $current, '<=')) {
                    $messages[] = 'Error uploading: '
                        . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                        . '. Uploaded version (' . $version . ') is not newer than current version (' . $current . ').';
                    continue;
                }
                // Remove old theme files
                $existing_themes = glob(self::$dir . '/' . $theme_slug . '_*');
                foreach ($existing_themes as $theme) {
                    if (is_file($theme)) {
                        unlink($theme);
                    }
                }
            }

            $theme_path = self::$dir . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $theme_path)) {
                if (isset($slug) && isset($version)) {
                    $conn->executeStatement(
                        'INSERT INTO themes (slug, version) VALUES (?, ?) '
                        . 'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                        [$slug, $version]
                    );
                }
                $messages[] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . ' uploaded successfully.';
            } else {
                $messages[] = 'Error uploading: '
                . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
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
