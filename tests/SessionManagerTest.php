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

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        // Reset the SessionManager singleton
        $ref = new \ReflectionClass(SessionManager::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        // Restore error and exception handlers
        while (error_reporting() !== E_ALL) {
            restore_error_handler();
        }
        restore_exception_handler();
    }

    public function testTimeoutExpiryInvalidatesSession(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';
        $session = SessionManager::getInstance();
        $session->start();
        $session->set('logged_in', true);
        $session->set('user_agent', 'Agent');
        $session->set('timeout', time() - 2000); // Set timeout in the past
        
        $result = $session->requireAuth();
        
        $this->assertFalse($result);
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testUserAgentChangeInvalidatesSession(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent2';
        $session = SessionManager::getInstance();
        $session->start();
        $session->set('logged_in', true);
        $session->set('user_agent', 'Agent1'); // Different user agent
        $session->set('timeout', time());
        
        $result = $session->requireAuth();
        
        $this->assertFalse($result);
        $this->assertSame(PHP_SESSION_NONE, session_status());
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

        $session = SessionManager::getInstance();
        $result = $session->requireAuth();

        $this->assertFalse($result);
        $this->assertSame(403, http_response_code());
        $conn->executeStatement('DELETE FROM blacklist');
    }

    public function testStartSetsCookieParams(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';
        $session = SessionManager::getInstance();
        $session->start();
        $params = session_get_cookie_params();
        $this->assertTrue($params['httponly']);
        $this->assertSame('Lax', $params['samesite']);
    }

    public function testRegenerateChangesSessionId(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';
        $session = SessionManager::getInstance();
        $session->start();
        $old = session_id();
        $session->regenerate();
        $this->assertNotSame($old, session_id());
    }

    public function testDestroyEndsSession(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';
        $session = SessionManager::getInstance();
        $session->start();
        $session->destroy();
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testRequireAuthWithValidSessionSucceeds(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';
        $session = SessionManager::getInstance();
        $session->start();
        $session->set('logged_in', true);
        $session->set('user_agent', 'Agent');
        $this->assertTrue($session->requireAuth());
    }

    public function testGetReturnDefaultValue(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';
        $session = SessionManager::getInstance();
        $session->start();
        $this->assertNull($session->get('nonexistent'));
        $this->assertSame('default', $session->get('nonexistent', 'default'));
        $this->assertSame(0, $session->get('nonexistent', 0));
    }
}
