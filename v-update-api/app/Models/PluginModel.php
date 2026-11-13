<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: PluginModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Helpers\ValidationHelper;
use Psr\Http\Message\UploadedFileInterface;

class PluginModel
{
    private const DIR = PLUGINS_DIR;

    /**
     * Return plugin version by slug, or null when not found.
     */
    public static function getVersionBySlug(string $slug): ?string
    {
        foreach (self::getPlugins() as $plugin) {
            if ($plugin['slug'] === $slug) {
                return $plugin['version'];
            }
        }

        return null;
    }

    /**
     * Return array of plugin data.
     *
     * @return array<int, array{slug: string, version: string}>
     */
    public static function getPlugins(): array
    {
        $files = glob(self::DIR . '/*.zip');
        if ($files === false) {
            error_log('PluginModel::getPlugins could not list package files in "' . self::DIR . '".');
            return [];
        }

        $pluginsBySlug = [];
        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $package = ValidationHelper::parsePackageFilename(basename($file));
            if ($package === null) {
                continue;
            }
            $slug = $package['slug'];
            if (!isset($pluginsBySlug[$slug]) || version_compare($package['version'], $pluginsBySlug[$slug]['version'], '>')) {
                $pluginsBySlug[$slug] = $package;
            }
        }

        ksort($pluginsBySlug, SORT_STRING);
        return array_values($pluginsBySlug);
    }

    /**
     * Delete a plugin file.
     */
    public static function deletePlugin(string $pluginName): bool
    {
        $basename = basename($pluginName);
        $parsed = ValidationHelper::parsePackageFilename($basename);
        if ($parsed === null) {
            error_log('Plugin delete rejected: invalid filename format "' . $basename . '".');
            return false;
        }

        $pluginPath = self::DIR . '/' . $basename;
        if (!file_exists($pluginPath)) {
            error_log('Plugin delete skipped: file missing "' . $pluginPath . '".');
            return false;
        }

        $realPath = realpath($pluginPath);
        $realDir = realpath(self::DIR);
        if ($realPath === false || $realDir === false || dirname($realPath) !== $realDir) {
            error_log('Plugin delete rejected: path outside plugin directory "' . $pluginPath . '".');
            return false;
        }

        if (!unlink($pluginPath)) {
            error_log('Plugin delete failed: unable to unlink "' . $pluginPath . '" (permission/race condition).');
            return false;
        }

        return true;
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
        $normalized = self::normalizeUploadPayload($fileArray);
        return self::processUploads($normalized['entries'], $normalized['errors'], $isAjax);
    }

    /**
     * Accept normalized PSR-7 uploaded files, including nested multi-file fields.
     *
     * @param array<mixed>|UploadedFileInterface $files
     * @return list<string>
     */
    public static function uploadPsrFiles(array|UploadedFileInterface $files, bool $isAjax = false): array
    {
        $entries = [];
        $errors = [];
        $collect = static function (mixed $value) use (&$collect, &$entries, &$errors): void {
            if (is_array($value)) {
                foreach ($value as $child) {
                    $collect($child);
                }
                return;
            }
            if (!$value instanceof UploadedFileInterface) {
                $errors[] = 'Error uploading: malformed PSR-7 uploaded file.';
                return;
            }
            $name = $value->getClientFilename();
            if ($name === null) {
                $errors[] = 'Error uploading: uploaded file is missing a filename.';
                return;
            }
            $entries[] = [
                'name' => $name,
                'tmp_name' => '',
                'error' => $value->getError(),
                'size' => $value->getSize() ?? 0,
                'uploaded_file' => $value,
            ];
        };
        $collect($files);
        return self::processUploads($entries, $errors, $isAjax);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param list<string> $messages
     * @return list<string>
     */
    private static function processUploads(array $entries, array $messages, bool $isAjax): array
    {
        $allowedExtensions = ['zip'];
        foreach ($entries as $entry) {
            $uploadedFile = $entry['uploaded_file'] ?? null;
            $originalFilename = basename($entry['name']);
            $fileName = ValidationHelper::validateFilename($entry['name']) ?? '';
            $fileError = $uploadedFile instanceof UploadedFileInterface
                ? $uploadedFile->getError()
                : $entry['error'];
            $fileSize = $uploadedFile instanceof UploadedFileInterface
                ? ($uploadedFile->getSize() ?? 0)
                : $entry['size'];
            $fileExtension = $fileName ? strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) : '';
            $parsedFilename = $fileName ? ValidationHelper::parsePackageFilename($fileName) : null;
            $pluginSlug = $parsedFilename['slug'] ?? '';
            $current = self::getVersionBySlug($pluginSlug);
            $maxUploadSize = min(
                self::parseIniSize(ini_get('upload_max_filesize')),
                self::parseIniSize(ini_get('post_max_size'))
            );

            if ($fileSize > $maxUploadSize) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                    '. File size exceeds the maximum allowed size of ' . ($maxUploadSize / (1024 * 1024)) . ' MB.';
                continue;
            }

            if ($fileError !== UPLOAD_ERR_OK || !in_array($fileExtension, $allowedExtensions)) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                    '. Only .zip files are allowed, and filenames must follow the format: plugin-name_1.0.zip';
                continue;
            }

            if ($fileName === '' || $parsedFilename === null) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                    '. Only .zip files are allowed, and filenames must follow the format: plugin-name_1.0.zip';
                continue;
            }

            $slug    = $parsedFilename['slug'];
            $version = $parsedFilename['version'];

            if ($current && version_compare($version, $current, '<=')) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                    '. Uploaded version (' . $version . ') is not newer than current version (' . $current . ').';
                continue;
            }

            $fileTmp = $uploadedFile instanceof UploadedFileInterface
                ? (string) $uploadedFile->getStream()->getMetadata('uri')
                : $entry['tmp_name'];

            if (class_exists('\\ZipArchive')) {
                $za = new \ZipArchive();
                if ($za->open($fileTmp) !== true) {
                    $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                        '. File is not a valid ZIP archive.';
                    continue;
                }
                $za->close();
            } else {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') .
                    '. ZIP validation unavailable: the PHP zip extension is not installed.';
                continue;
            }

            $tempPath = self::DIR . '/' . uniqid('tmp_upload_', true) . '.zip';
            $finalPath = self::DIR . '/' . $fileName;
            try {
                if ($uploadedFile instanceof UploadedFileInterface) {
                    $uploadedFile->moveTo($tempPath);
                    $moved = is_file($tempPath);
                } else {
                    $moved = move_uploaded_file($fileTmp, $tempPath);
                }
            } catch (\Throwable) {
                $moved = false;
            }
            if (!$moved) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8');
                continue;
            }

            $result = self::persistUploadedArtifact($slug, $tempPath, $finalPath);
            if (!$result['success']) {
                $messages[] = 'Error uploading: ' . htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8');
                error_log($result['error']);
                continue;
            }

            $messages[] = htmlspecialchars($originalFilename, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
        
        }
        return $messages;
    }

    /**
     * Persist an uploaded ZIP while keeping only one version for its slug.
     *
     * @return array{success: bool, error: string}
     */
    private static function persistUploadedArtifact(
        string $slug,
        string $tempPath,
        string $finalPath
    ): array {
        $deletedBackups = [];
        $movedToFinal = false;

        try {
            if (!@rename($tempPath, $finalPath)) {
                throw new \RuntimeException('Failed to move staged upload into final path.');
            }
            $movedToFinal = true;
            $finalRealPath = realpath($finalPath);
            if ($finalRealPath === false) {
                throw new \RuntimeException('Unable to resolve the uploaded plugin path.');
            }

            $existing = glob(self::DIR . '/' . $slug . '_*.zip');
            if ($existing === false) {
                throw new \RuntimeException('Failed to list existing plugin artifacts.');
            }

            foreach ($existing as $artifact) {
                if (!is_file($artifact) || realpath($artifact) === $finalRealPath) {
                    continue;
                }
                $existingPackage = ValidationHelper::parsePackageFilename(basename($artifact));
                if ($existingPackage === null || $existingPackage['slug'] !== $slug) {
                    continue;
                }
                $backupPath = $artifact . '.bak_upload_' . uniqid('', true);
                if (!@rename($artifact, $backupPath)) {
                    throw new \RuntimeException('Failed to stage old plugin artifact for replacement.');
                }
                $deletedBackups[] = ['original' => $artifact, 'backup' => $backupPath];
            }

            foreach ($deletedBackups as $backup) {
                @unlink($backup['backup']);
            }

            return ['success' => true, 'error' => ''];
        } catch (\Throwable $exception) {
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
                'error' => 'Plugin upload failed for slug "' . $slug . '": ' . $exception->getMessage(),
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
                'uploaded_file' => null,
            ],
            'error' => null,
        ];
    }

    /**
     * Parse a size string from php.ini into bytes.
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

}



