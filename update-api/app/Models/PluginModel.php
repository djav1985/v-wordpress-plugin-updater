<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: PluginModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\DatabaseManager;
use App\Helpers\ValidationHelper;

class PluginModel
{
    public static string $dir = PLUGINS_DIR;

    /**
     * Return array of plugin data.
     *
     * @return array<int, array{slug: string, version: string}>
     */
    public static function getPlugins(): array
    {
        $conn = DatabaseManager::getConnection();
        $rows = $conn->fetchAllAssociative('SELECT slug, version FROM plugins ORDER BY slug');
        $plugins = [];
        foreach ($rows as $row) {
            $plugins[] = [
                'slug' => $row['slug'],
                'version' => $row['version'],
            ];
        }
        return $plugins;
    }

    /**
     * Delete a plugin file.
     *
     * @param string $pluginName
     *
     * @return bool True on success, false otherwise.
     */
    public static function deletePlugin(string $pluginName): bool
    {
        $pluginPath = self::$dir . '/' . basename($pluginName);
        if (
            file_exists($pluginPath) &&
            dirname(realpath($pluginPath)) === realpath(self::$dir)
        ) {
            unlink($pluginPath);
            $slug = explode('_', basename($pluginName))[0];
            $conn = DatabaseManager::getConnection();
            $conn->executeStatement('DELETE FROM plugins WHERE slug = ?', [$slug]);
            return true;
        }

        return false;
    }

    /**
     * Upload plugin files.
     *
     * @param array<string, array<int, mixed>> $fileArray $_FILES['plugin_file'] structure
     * @param bool                              $isAjax    Whether the request was via AJAX
     *
     * @return string[] Array of status messages
     */
    public static function uploadFiles(array $fileArray, bool $isAjax = false): array
    {
        $messages = [];
        $allowedExtensions = ['zip'];
        $totalFiles = count($fileArray['name']);

        for ($i = 0; $i < $totalFiles; $i++) {
            $fileName = isset($fileArray['name'][$i]) ? ValidationHelper::validateFilename($fileArray['name'][$i]) : '';
            $fileTmp = isset($fileArray['tmp_name'][$i]) ? $fileArray['tmp_name'][$i] : '';
            $fileError = isset($fileArray['error'][$i]) ? filter_var($fileArray['error'][$i], FILTER_VALIDATE_INT) : UPLOAD_ERR_NO_FILE;
            
            // Use the original filename for error messages if validation failed
            if (isset($fileArray['name'][$i])) {
                if (is_array($fileArray['name'])) {
                    $originalFilename = basename($fileArray['name'][$i]);
                } else {
                    $originalFilename = basename($fileArray['name']);
                }
            } else {
                $originalFilename = 'unknown';
            }
            $fileExtension = $fileName ? strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) : '';

            $pluginSlug = $fileName ? explode('_', $fileName)[0] : '';
            $conn = DatabaseManager::getConnection();
            $current = $conn->fetchOne('SELECT version FROM plugins WHERE slug = ?', [$pluginSlug]);
            $maxUploadSize = min(
                self::_parseIniSize(ini_get('upload_max_filesize')),
                self::_parseIniSize(ini_get('post_max_size'))
            );

            if ($fileArray['size'][$i] > $maxUploadSize) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                    '. File size exceeds the maximum allowed size of ' . ($maxUploadSize / (1024 * 1024)) . ' MB.';
                continue;
            }

            if ($fileError !== UPLOAD_ERR_OK || !in_array($fileExtension, $allowedExtensions)) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                    '. Only .zip files are allowed, and filenames must follow the format: plugin-name_1.0.zip';
                continue;
            }

            if ($fileName && preg_match('/^([A-Za-z0-9_-]+)_([\d\.]+)\.zip$/', $fileName, $matches)) {
                $slug = $matches[1];
                $version = $matches[2];
                if ($current && version_compare($version, $current, '<=')) {
                    $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                        '. Uploaded version (' . $version . ') is not newer than current version (' . $current . ').';
                    continue;
                }
                // Remove old plugin files
                $existingPlugins = glob(self::$dir . '/' . $pluginSlug . '_*');
                foreach ($existingPlugins as $plugin) {
                    if (is_file($plugin)) {
                        unlink($plugin);
                    }
                }
            }

            if ($fileName) {
                $pluginPath = self::$dir . '/' . $fileName;
                if (move_uploaded_file($fileTmp, $pluginPath)) {
                    if (isset($slug) && isset($version)) {
                        $conn->executeStatement(
                            'INSERT INTO plugins (slug, version) VALUES (?, ?) '
                            . 'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                            [$slug, $version]
                        );
                    }
                    $messages[] = htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
                } else {
                    $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8');
                }
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
