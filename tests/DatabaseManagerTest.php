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
        if (!is_dir(dirname(DB_FILE))) {
            mkdir(dirname(DB_FILE), 0777, true);
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        $ref  = new \ReflectionClass(DatabaseManager::class);
        $prop = $ref->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    protected function tearDown(): void
    {
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
    }

    public function testGetConnectionCreatesFileAndSingleton(): void
    {
        $conn1 = DatabaseManager::getConnection();
        $this->assertInstanceOf(Connection::class, $conn1);
        $this->assertFileExists(DB_FILE);

        $conn2 = DatabaseManager::getConnection();
        $this->assertSame($conn1, $conn2);
    }
}
