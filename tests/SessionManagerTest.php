<?php

namespace Tests;

// Ensure the autoloader is loaded from the correct location
require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\SessionManager;
use App\Core\DatabaseManager;

class SessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('SESSION_TIMEOUT_LIMIT')) {
            define('SESSION_TIMEOUT_LIMIT', 1800);
        }
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/session.sqlite');
        }
        $ref = new \ReflectionClass(DatabaseManager::class);
        $prop = $ref->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('CREATE TABLE IF NOT EXISTS blacklist (ip TEXT PRIMARY KEY, login_attempts INTEGER, blacklisted INTEGER, timestamp INTEGER)');
        $conn->executeStatement('DELETE FROM blacklist');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public function testTimeoutExpiryInvalidatesSession(): void
    {
        $basePath = dirname(__DIR__);
        $code = <<<'PHP'
require 'update-api/vendor/autoload.php';
if (!defined('SESSION_TIMEOUT_LIMIT')) define('SESSION_TIMEOUT_LIMIT', 1800);
if (!defined('DB_FILE')) define('DB_FILE', getcwd().'/update-api/storage/test.sqlite');
$_SERVER['HTTP_USER_AGENT'] = 'Agent';
$session = \App\Core\SessionManager::getInstance();
$session->start();
$session->set('logged_in', true);
$session->set('user_agent', 'Agent');
$session->set('timeout', time() - (SESSION_TIMEOUT_LIMIT + 1));
register_shutdown_function(function(){ echo session_status(); });
$session->requireAuth();
PHP;
        $cmd = 'cd ' . escapeshellarg($basePath) . ' && php -r ' . escapeshellarg($code);
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);
        $this->assertSame('1', $output[0] ?? null);
    }

    public function testUserAgentChangeInvalidatesSession(): void
    {
        $basePath = dirname(__DIR__);
        $code = <<<'PHP'
require 'update-api/vendor/autoload.php';
if (!defined('SESSION_TIMEOUT_LIMIT')) define('SESSION_TIMEOUT_LIMIT', 1800);
if (!defined('DB_FILE')) define('DB_FILE', getcwd().'/update-api/storage/test.sqlite');
$_SERVER['HTTP_USER_AGENT'] = 'Agent2';
$session = \App\Core\SessionManager::getInstance();
$session->start();
$session->set('logged_in', true);
$session->set('user_agent', 'Agent1');
$session->set('timeout', time());
register_shutdown_function(function(){ echo session_status(); });
$session->requireAuth();
PHP;
        $cmd = 'cd ' . escapeshellarg($basePath) . ' && php -r ' . escapeshellarg($code);
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);
        $this->assertSame('1', $output[0] ?? null);
    }

    public function testRequireAuthBlocksBlacklistedIp(): void
    {
        $ip = '10.0.0.1';
        $_SERVER['REMOTE_ADDR'] = $ip;
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';

        $conn = DatabaseManager::getConnection();
        $conn->insert('blacklist', [
            'ip' => $ip,
            'login_attempts' => 3,
            'blacklisted' => 1,
            'timestamp' => time(),
        ]);

        $logFile = __DIR__ . '/../update-api/php_app.log';
        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $session = SessionManager::getInstance();
        $result = $session->requireAuth();

        $this->assertFalse($result);
        $this->assertSame(403, http_response_code());
        $logAfter = file_get_contents($logFile);
        $this->assertStringContainsString($ip, $logAfter);
        $conn->executeStatement('DELETE FROM blacklist');
        restore_error_handler();
        restore_exception_handler();
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }
}
