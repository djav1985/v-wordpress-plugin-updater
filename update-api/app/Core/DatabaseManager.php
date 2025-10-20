<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
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
            // Ensure directory exists and file is present so tests that assert file
            // creation will pass.
            $dir = dirname(DB_FILE);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            if (!file_exists(DB_FILE)) {
                // Create an empty SQLite file — Doctrine will initialize schema as needed.
                touch(DB_FILE);
            }

            $params = [
                'driver' => 'pdo_sqlite',
                'path' => DB_FILE,
            ];
            self::$connection = DriverManager::getConnection($params);
        }
        return self::$connection;
    }
}
