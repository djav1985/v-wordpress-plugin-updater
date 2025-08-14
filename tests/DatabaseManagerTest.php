<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseManager;
use Doctrine\DBAL\Connection;

class DatabaseManagerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', __DIR__ . '/../update-api/storage/test.sqlite');
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
    }

    public function testGetConnection(): void
    {
        $conn = DatabaseManager::getConnection();
        $this->assertInstanceOf(Connection::class, $conn);
    }
}
