<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: DatabaseManager.php
 * Description: WordPress Update API
 */

namespace App\Core;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DatabaseManager
{
    private static ?Connection $connection = null;

    /**
     * Get a Doctrine DBAL connection to the SQLite database.
     */
    public static function getConnection(): Connection
    {
        if (self::$connection === null) {
            $dir = dirname(DB_FILE);
            if (!is_dir($dir)) {
                self::createDirectory($dir);
            }

            if (!file_exists(DB_FILE)) {
                self::createDatabaseFile(DB_FILE);
            }

            $params = [
                'driver' => 'pdo_sqlite',
                'path' => DB_FILE,
            ];
            self::$connection = DriverManager::getConnection($params);
        }

        return self::$connection;
    }

    /**
     * @throws \RuntimeException
     */
    private static function createDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        $error = null;
        set_error_handler(static function (int $severity, string $message) use (&$error): bool {
            $error = $message;
            return true;
        });

        try {
            $created = mkdir($dir, 0750, true);
        } finally {
            restore_error_handler();
        }

        if ($created !== true && !is_dir($dir)) {
            $context = 'DatabaseManager failed to create database directory: ' . $dir
                . ($error !== null ? ' (' . $error . ')' : '');
            ErrorManager::log($context);
            throw new \RuntimeException($context);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private static function createDatabaseFile(string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        $error = null;
        set_error_handler(static function (int $severity, string $message) use (&$error): bool {
            $error = $message;
            return true;
        });

        try {
            $created = touch($path);
        } finally {
            restore_error_handler();
        }

        if ($created !== true || !file_exists($path)) {
            $context = 'DatabaseManager failed to initialize database file: ' . $path
                . ($error !== null ? ' (' . $error . ')' : '');
            ErrorManager::log($context);
            throw new \RuntimeException($context);
        }
    }
}
