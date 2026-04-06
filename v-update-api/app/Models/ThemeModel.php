<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: ThemeModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\DatabaseManager;
use App\Helpers\ValidationHelper;

class ThemeModel
{
    private const DIR = THEMES_DIR;

    /**
     * Return theme version by slug, or null when not found.
     *
     * @param string $slug Theme slug.
     * @return string|null Theme version or null if not found.
     */
    public static function getVersionBySlug(string $slug): ?string
    {
        $version = DatabaseManager::connection()->fetchOne('SELECT version FROM themes WHERE slug = ?', [$slug]);
        if ($version === false || $version === null) {
            return null;
        }

        return (string) $version;
    }

    /**
     * Return array of theme data.
     *
     * @return array<int, array{slug: string, version: string}> Array of theme data.
     */
    public static function getThemes(): array
    {
        $rows = DatabaseManager::connection()->fetchAllAssociative('SELECT slug, version FROM themes ORDER BY slug');
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
     * @param string $themeName Theme file name to delete.
     * @return bool True if deleted successfully, false otherwise.
     */
    public static function deleteTheme(string $themeName): bool
    {
        $basename = basename($themeName);
        $parsed = ValidationHelper::parsePackageFilename($basename);
        if ($parsed === null) {
            error_log('Theme delete rejected: invalid filename format "' . $basename . '".');
            return false;
        }

        $slug = $parsed['slug'];
        $themePath = self::DIR . '/' . $basename;
        if (!file_exists($themePath)) {
            error_log('Theme delete skipped: file missing "' . $themePath . '".');
            return false;
        }

        $realPath = realpath($themePath);
        $realDir = realpath(self::DIR);
        if ($realPath === false || $realDir === false || dirname($realPath) !== $realDir) {
            error_log('Theme delete rejected: path outside theme directory "' . $themePath . '".');
            return false;
        }

        if (!unlink($themePath)) {
            error_log('Theme delete failed: unable to unlink "' . $themePath . '" (permission/race condition).');
            return false;
        }

        DatabaseManager::connection()->executeStatement('DELETE FROM themes WHERE slug = ?', [$slug]);
        return true;
    }

    /**
     * Upload theme files.
     *
     * @param array<string, array<int, mixed>> $fileArray $_FILES['theme_file'] structure.
     * @param bool                              $isAjax    Whether the request was via AJAX.
     *
     * @return string[] Array of status messages.
     */
    public static function uploadFiles(array $fileArray, bool $isAjax = false): array
    {
        $messages = [];
        $allowedExtensions = ['zip'];
        $normalized = self::normalizeUploadPayload($fileArray);
        foreach ($normalized['errors'] as $errorMessage) {
            $messages[] = $errorMessage;
        }

        foreach ($normalized['entries'] as $entry) {
            $originalFilename = basename($entry['name']);
            $fileName = ValidationHelper::validateFilename($entry['name']) ?? '';
            $fileTmp = $entry['tmp_name'];
            $fileError = $entry['error'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $parsedFilename = $fileName ? ValidationHelper::parsePackageFilename($fileName) : null;
            $themeSlug = $parsedFilename['slug'] ?? '';
            $current = DatabaseManager::connection()->fetchOne('SELECT version FROM themes WHERE slug = ?', [$themeSlug]);
            $maxUploadSize = min(
                self::parseIniSize(ini_get('upload_max_filesize')),
                self::parseIniSize(ini_get('post_max_size'))
            );

            if ($entry['size'] > $maxUploadSize) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8')
                    . '. File size exceeds the maximum allowed size of '
                    . ($maxUploadSize / (1024 * 1024)) . ' MB.';
                continue;
            }

            if (
                $fileError !== UPLOAD_ERR_OK
                || !in_array($fileExtension, $allowedExtensions)
            ) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8')
                    . '. Only .zip files are allowed, and filenames must follow the format: theme-name_1.0.zip';
                continue;
            }

            if ($fileName === '' || $parsedFilename === null) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8')
                    . '. Only .zip files are allowed, and filenames must follow the format: theme-name_1.0.zip';
                continue;
            }

            $slug    = $parsedFilename['slug'];
            $version = $parsedFilename['version'];

            if ($current && version_compare($version, $current, '<=')) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8')
                    . '. Uploaded version (' . $version . ') is not newer than current version (' . $current . ').';
                continue;
            }

            if (class_exists('\\ZipArchive')) {
                $za = new \ZipArchive();
                if ($za->open($fileTmp) !== true) {
                    $messages[] = 'Error uploading: '
                        . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8')
                        . '. File is not a valid ZIP archive.';
                    continue;
                }
                $za->close();
            } else {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8')
                    . '. ZIP validation unavailable: the PHP zip extension is not installed.';
                continue;
            }

            $tempPath  = self::DIR . '/' . uniqid('tmp_upload_', true) . '.zip';
            $finalPath = self::DIR . '/' . $fileName;
            if (!move_uploaded_file($fileTmp, $tempPath)) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8');
                continue;
            }

            $result = self::persistUploadedArtifact('themes', $slug, $version, $tempPath, $finalPath);
            if (!$result['success']) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8');
                error_log($result['error']);
                continue;
            }

            $messages[] = htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8')
                . ' uploaded successfully.';
        }

        return $messages;
    }

    /**
     * Persist upload with transactional DB update and filesystem compensation.
     *
     * @param string $table     Database table name.
     * @param string $slug      Theme slug.
     * @param string $version   Theme version.
     * @param string $tempPath  Temporary file path.
     * @param string $finalPath Final destination path.
     * @return array{success: bool, error: string} Result array with success status and error message.
     */
    private static function persistUploadedArtifact(
        string $table,
        string $slug,
        string $version,
        string $tempPath,
        string $finalPath
    ): array {
        $deletedBackups = [];
        $movedToFinal = false;

        try {
            DatabaseManager::connection()->beginTransaction();

            if (!rename($tempPath, $finalPath)) {
                throw new \RuntimeException('Failed to move staged upload into final path.');
            }
            $movedToFinal = true;

            $existing = glob(self::DIR . '/' . $slug . '_*');
            if ($existing === false) {
                throw new \RuntimeException('Failed to list existing theme artifacts.');
            }

            foreach ($existing as $artifact) {
                if (!is_file($artifact) || $artifact === $finalPath) {
                    continue;
                }
                $backupPath = $artifact . '.bak_upload_' . uniqid('', true);
                if (!rename($artifact, $backupPath)) {
                    throw new \RuntimeException('Failed to stage old theme artifact for replacement.');
                }
                $deletedBackups[] = ['original' => $artifact, 'backup' => $backupPath];
            }

            DatabaseManager::connection()->executeStatement(
                "INSERT INTO $table (slug, version) VALUES (?, ?) "
                . 'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                [$slug, $version]
            );

            DatabaseManager::connection()->commit();

            foreach ($deletedBackups as $backup) {
                @unlink($backup['backup']);
            }

            return ['success' => true, 'error' => ''];
        } catch (\Throwable $exception) {
            if (DatabaseManager::connection()->isTransactionActive()) {
                DatabaseManager::connection()->rollBack();
            }

            foreach ($deletedBackups as $backup) {
                if (file_exists($backup['backup'])) {
                    @rename($backup['backup'], $backup['original']);
                }
            }

            if ($movedToFinal && file_exists($finalPath)) {
                @unlink($finalPath);
            }
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            return [
                'success' => false,
                'error' => 'Theme upload transaction failed for slug "' . $slug . '": ' . $exception->getMessage(),
            ];
        }
    }

    /**
     * Normalize upload payload into a predictable list structure.
     *
     * @param array<string, mixed> $fileArray The raw $_FILES upload array.
     * @return array{entries: array<int, array{name: string, tmp_name: string, error: int, size: int}>, errors: string[]} Normalized upload entries and errors.
     */
    private static function normalizeUploadPayload(array $fileArray): array
    {
        $requiredKeys = ['name', 'tmp_name', 'error', 'size'];
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $fileArray)) {
                return [
                    'entries' => [],
                    'errors' => ['Error uploading: malformed upload payload (missing "' . $requiredKey . '").'],
                ];
            }
        }

        $isMulti = is_array($fileArray['name'])
            || is_array($fileArray['tmp_name'])
            || is_array($fileArray['error'])
            || is_array($fileArray['size']);

        $entries = [];
        $errors = [];

        if (!$isMulti) {
            $single = self::buildEntry(
                $fileArray['name'],
                $fileArray['tmp_name'],
                $fileArray['error'],
                $fileArray['size'],
                0
            );
            if ($single['entry'] !== null) {
                $entries[] = $single['entry'];
            }
            if ($single['error'] !== null) {
                $errors[] = $single['error'];
            }
            return ['entries' => $entries, 'errors' => $errors];
        }

        if (!is_array($fileArray['name']) || !is_array($fileArray['tmp_name']) || !is_array($fileArray['error']) || !is_array($fileArray['size'])) {
            return [
                'entries' => [],
                'errors' => ['Error uploading: malformed upload payload (mixed single/multi-file format).'],
            ];
        }

        $totalFiles = count($fileArray['name']);
        for ($i = 0; $i < $totalFiles; $i++) {
            $single = self::buildEntry(
                $fileArray['name'][$i] ?? null,
                $fileArray['tmp_name'][$i] ?? null,
                $fileArray['error'][$i] ?? null,
                $fileArray['size'][$i] ?? null,
                $i
            );
            if ($single['entry'] !== null) {
                $entries[] = $single['entry'];
            }
            if ($single['error'] !== null) {
                $errors[] = $single['error'];
            }
        }

        return ['entries' => $entries, 'errors' => $errors];
    }

    /**
     * Build one normalized upload entry, or a descriptive validation error.
     *
     * @param mixed $name    File name.
     * @param mixed $tmpName Temporary file name.
     * @param mixed $error   Upload error code.
     * @param mixed $size    File size.
     * @param int   $index   File index in upload array.
     * @return array{entry: array{name: string, tmp_name: string, error: int, size: int}|null, error: string|null} Entry or error.
     */
    private static function buildEntry(mixed $name, mixed $tmpName, mixed $error, mixed $size, int $index): array
    {
        if (!is_string($name) || !is_string($tmpName)) {
            return [
                'entry' => null,
                'error' => 'Error uploading: malformed upload entry at index ' . $index . ' (name/tmp_name must be strings).',
            ];
        }

        $parsedError = filter_var($error, FILTER_VALIDATE_INT);
        $parsedSize = filter_var($size, FILTER_VALIDATE_INT);
        if ($parsedError === false || $parsedSize === false) {
            return [
                'entry' => null,
                'error' => 'Error uploading: malformed upload entry at index ' . $index . ' (error/size must be integers).',
            ];
        }

        return [
            'entry' => [
                'name' => $name,
                'tmp_name' => $tmpName,
                'error' => $parsedError,
                'size' => max(0, $parsedSize),
            ],
            'error' => null,
        ];
    }

    /**
     * Parse a size string from php.ini into bytes.
     *
     * @param string $size Size string (e.g., '10M', '1G').
     * @return int Size in bytes.
     */
    private static function parseIniSize(string $size): int
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

    /**
     * Sync ZIP files from directory into the themes table.
     *
     * @param string $dir Directory path to sync from.
     * @return void
     */
    public static function syncFromDirectory(string $dir): void
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            error_log(sprintf('ThemeModel::syncFromDirectory cannot read directory "%s".', $dir));
            return;
        }

        $files = glob($dir . '/*.zip');
        if ($files === false) {
            error_log(sprintf('ThemeModel::syncFromDirectory glob() failed for directory "%s".', $dir));
            return;
        }

        $found = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (preg_match('/^(.+)_([\d\.]+)\.zip$/', $name, $matches)) {
                $slug = $matches[1];
                $version = $matches[2];
                $found[$slug] = true;
                DatabaseManager::connection()->executeStatement(
                    'INSERT INTO themes (slug, version) VALUES (?, ?) ' .
                    'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                    [$slug, $version]
                );
            }
        }

        $rows = DatabaseManager::connection()->fetchAllAssociative('SELECT slug FROM themes');
        foreach ($rows as $row) {
            if (!isset($found[$row['slug']])) {
                DatabaseManager::connection()->executeStatement('DELETE FROM themes WHERE slug = ?', [$row['slug']]);
            }
        }
    }
}



