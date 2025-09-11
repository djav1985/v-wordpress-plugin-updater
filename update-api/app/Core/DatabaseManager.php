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
            $params = [
                'driver' => 'pdo_sqlite',
                'path' => DB_FILE,
            ];
            self::$connection = DriverManager::getConnection($params);
        }
        return self::$connection;
    }
}
