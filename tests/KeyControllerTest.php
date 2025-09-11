<?php
namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseManager;
use App\Controllers\KeyController;
use App\Helpers\Encryption;

class KeyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', __DIR__ . '/../update-api/storage/test.sqlite');
        }
        if (!defined('ENCRYPTION_KEY')) {
            define('ENCRYPTION_KEY', 'secret');
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('CREATE TABLE hosts (domain TEXT PRIMARY KEY, key TEXT, send_auth INTEGER)');
        $conn->executeStatement('CREATE TABLE blacklist (ip TEXT PRIMARY KEY, login_attempts INTEGER, blacklisted INTEGER, timestamp INTEGER)');
        $key = Encryption::encrypt('abc123');
        $conn->executeStatement('INSERT INTO hosts (domain, key, send_auth) VALUES (?, ?, 1)', ['example.com', $key]);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['type' => 'auth', 'domain' => 'example.com'];
    }

    protected function tearDown(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DROP TABLE IF EXISTS hosts');
        $conn->executeStatement('DROP TABLE IF EXISTS blacklist');
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        http_response_code(200);
    }

    public function testSendAuthToggleAndDenial(): void
    {
        $controller = new KeyController();
        ob_start();
        $controller->handleRequest();
        $output = ob_get_clean();
        $this->assertSame('abc123', $output);
        $conn = DatabaseManager::getConnection();
        $row = $conn->fetchAssociative('SELECT send_auth FROM hosts WHERE domain = ?', ['example.com']);
        $this->assertSame(0, (int)$row['send_auth']);
        ob_start();
        $controller->handleRequest();
        ob_end_clean();
        $this->assertSame(403, http_response_code());
    }
}
