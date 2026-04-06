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
        $themePath = self::$dir . '/' . $basename;
        if (!file_exists($themePath)) {
            error_log('Theme delete skipped: file missing "' . $themePath . '".');
            return false;
        }

        $realPath = realpath($themePath);
        $realDir = realpath(self::$dir);
        if ($realPath === false || $realDir === false || dirname($realPath) !== $realDir) {
            error_log('Theme delete rejected: path outside theme directory "' . $themePath . '".');
            return false;
        }

        if (!unlink($themePath)) {
            error_log('Theme delete failed: unable to unlink "' . $themePath . '" (permission/race condition).');
            return false;
        }

        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DELETE FROM themes WHERE slug = ?', [$slug]);
        return true;
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
            $conn = DatabaseManager::getConnection();
            $current = $conn->fetchOne('SELECT version FROM themes WHERE slug = ?', [$themeSlug]);
            $maxUploadSize = min(
                self::_parseIniSize(ini_get('upload_max_filesize')),
                self::_parseIniSize(ini_get('post_max_size'))
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

            $tempPath  = self::$dir . '/' . uniqid('tmp_upload_', true) . '.zip';
            $finalPath = self::$dir . '/' . $fileName;
            if (!move_uploaded_file($fileTmp, $tempPath)) {
                $messages[] = 'Error uploading: '
                    . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8');
                continue;
            }

            $result = self::persistUploadedArtifact($conn, 'themes', $slug, $version, $tempPath, $finalPath);
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
     * @return array{success: bool, error: string}
     */
    private static function persistUploadedArtifact(
        \Doctrine\DBAL\Connection $conn,
        string $table,
        string $slug,
        string $version,
        string $tempPath,
        string $finalPath
    ): array {
        $deletedBackups = [];
        $movedToFinal = false;

        try {
            $conn->beginTransaction();

            if (!rename($tempPath, $finalPath)) {
                throw new \RuntimeException('Failed to move staged upload into final path.');
            }
            $movedToFinal = true;

            $existing = glob(self::$dir . '/' . $slug . '_*');
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

            $conn->executeStatement(
                "INSERT INTO $table (slug, version) VALUES (?, ?) "
                . 'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                [$slug, $version]
            );

            $conn->commit();

            foreach ($deletedBackups as $backup) {
                @unlink($backup['backup']);
            }

            return ['success' => true, 'error' => ''];
        } catch (\Throwable $exception) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
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
     * @param array<string, mixed> $fileArray
     * @return array{entries: array<int, array{name: string, tmp_name: string, error: int, size: int}>, errors: string[]}
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
     * @return array{entry: array{name: string, tmp_name: string, error: int, size: int}|null, error: string|null}
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
