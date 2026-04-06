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
use App\Helpers\ValidationHelper;

class ThemeModel
{
    public static string $dir = THEMES_DIR;

    /**
     * Return array of theme data.
     *
     * @return array<int, array{slug: string, version: string}>
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
     * @param string $themeName
     *
     * @return bool True on success, false otherwise.
     */
    public static function deleteTheme(string $themeName): bool
    {
        $basename = basename($themeName);
        $parsed = ValidationHelper::parsePackageFilename($basename);
        if ($parsed === null) {
            return false;
        }

        $slug = $parsed['slug'];
        $themePath = self::$dir . '/' . $basename;
        if (
            file_exists($themePath) &&
            dirname(realpath($themePath)) === realpath(self::$dir)
        ) {
            unlink($themePath);
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
        $allowedExtensions = ['zip'];
        $totalFiles = count($fileArray['name']);

        for ($i = 0; $i < $totalFiles; $i++) {
            $fileName = isset($fileArray['name'][$i])
                ? ValidationHelper::validateFilename($fileArray['name'][$i])
                : '';
            $fileTmp = isset($fileArray['tmp_name'][$i])
                ? $fileArray['tmp_name'][$i]
                : '';
            $fileError = isset($fileArray['error'][$i])
                ? filter_var($fileArray['error'][$i], FILTER_VALIDATE_INT)
                : UPLOAD_ERR_NO_FILE;
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $parsedFilename = $fileName ? ValidationHelper::parsePackageFilename($fileName) : null;
            $themeSlug = $parsedFilename['slug'] ?? '';
            $conn = DatabaseManager::getConnection();
            $current = $conn->fetchOne('SELECT version FROM themes WHERE slug = ?', [$themeSlug]);
            $maxUploadSize = min(
                self::_parseIniSize(ini_get('upload_max_filesize')),
                self::_parseIniSize(ini_get('post_max_size'))
            );

            if ($fileArray['size'][$i] > $maxUploadSize) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
                    . '. File size exceeds the maximum allowed size of '
                    . ($maxUploadSize / (1024 * 1024)) . ' MB.';
                continue;
            }

            if (
                $fileError !== UPLOAD_ERR_OK
                || !in_array($fileExtension, $allowedExtensions)
            ) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
                    . '. Only .zip files are allowed, and filenames must follow the format: theme-name_1.0.zip';
                continue;
            }

            // Validate filename format before touching the filesystem.
            if ($fileName === '' || $parsedFilename === null) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
                    . '. Only .zip files are allowed, and filenames must follow the format: theme-name_1.0.zip';
                continue;
            }

            $slug    = $parsedFilename['slug'];
            $version = $parsedFilename['version'];

            if ($current && version_compare($version, $current, '<=')) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
                    . '. Uploaded version (' . $version . ') is not newer than current version (' . $current . ').';
                continue;
            }

            if (class_exists('\\ZipArchive')) {
                $za = new \ZipArchive();
                if ($za->open($fileTmp) !== true) {
                    $messages[] = 'Error uploading: '
                        . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
                        . '. File is not a valid ZIP archive.';
                    continue;
                }
                $za->close();
            } else {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
                    . '. ZIP validation unavailable: the PHP zip extension is not installed.';
                continue;
            }

            // Save to a temporary file in the target directory first.
            $tempPath  = self::$dir . '/' . uniqid('tmp_upload_', true) . '.zip';
            $finalPath = self::$dir . '/' . $fileName;

            if (!move_uploaded_file($fileTmp, $tempPath)) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                continue;
            }

            // Atomic rename into the final filename.
            if (!rename($tempPath, $finalPath)) {
                @unlink($tempPath);
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
                continue;
            }

            // Only after a successful rename: delete superseded files and upsert DB.
            $existingThemes = glob(self::$dir . '/' . $themeSlug . '_*');
            if ($existingThemes !== false) {
                foreach ($existingThemes as $theme) {
                    if (is_file($theme) && $theme !== $finalPath) {
                        unlink($theme);
                    }
                }
            }

            $conn->executeStatement(
                'INSERT INTO themes (slug, version) VALUES (?, ?) '
                . 'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                [$slug, $version]
            );

            $messages[] = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8')
                . ' uploaded successfully.';
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
