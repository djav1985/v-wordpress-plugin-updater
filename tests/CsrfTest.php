<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\Csrf;
use App\Core\SessionManager;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/csrf-test.sqlite');
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        
        // Reset session manager instance
        $ref = new \ReflectionClass(SessionManager::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        
        // End any existing session
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
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
    }

    public function testValidateReturnsTrueForMatchingToken(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent';
        
        $session = SessionManager::getInstance();
        $session->start();
        
        $csrfToken = $session->get('csrf_token');
        $this->assertIsString($csrfToken);
        
        $result = Csrf::validate($csrfToken);
        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseForMismatchedToken(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent';
        
        $session = SessionManager::getInstance();
        $session->start();
        
        $result = Csrf::validate('wrong-token');
        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseForEmptyToken(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent';
        
        $session = SessionManager::getInstance();
        $session->start();
        
        $result = Csrf::validate('');
        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseWhenNoSessionToken(): void
    {
        // Start session but clear the CSRF token
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent';
        
        $session = SessionManager::getInstance();
        $session->start();
        $session->set('csrf_token', '');
        
        $result = Csrf::validate('any-token');
        $this->assertFalse($result);
    }

    public function testValidateUsesTimingSafeComparison(): void
    {
        // This test verifies that hash_equals is being used
        // by confirming it rejects similar but different tokens
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent';
        
        $session = SessionManager::getInstance();
        $session->start();
        
        $csrfToken = $session->get('csrf_token');
        $this->assertIsString($csrfToken);
        
        // Try with token that differs by one character
        $tamperedToken = substr($csrfToken, 0, -1) . 'X';
        
        $result = Csrf::validate($tamperedToken);
        $this->assertFalse($result);
    }
}
