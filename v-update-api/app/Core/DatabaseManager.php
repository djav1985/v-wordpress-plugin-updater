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
    private Connection $connection;

    /**
     * Initialize the database connection.
     */
    public function __construct()
    {
        $params = [
            'driver' => 'pdo_sqlite',
            'path' => DB_FILE,
        ];
        $this->connection = DriverManager::getConnection($params);
    }

    /**
     * Get the Doctrine DBAL connection to the SQLite database.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
