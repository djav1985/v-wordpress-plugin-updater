<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use App\Core\DatabaseManager;

class MessageHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/message-test.sqlite');
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        
        // Reset session manager instance
        $ref = new \ReflectionClass(SessionManager::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        
        // Reset database manager
        $dbRef = new \ReflectionClass(DatabaseManager::class);
        $dbProp = $dbRef->getProperty('connection');
        $dbProp->setAccessible(true);
        $dbProp->setValue(null, null);
        
        // End any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent';
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

    public function testAddMessageStoresInSession(): void
    {
        $session = SessionManager::getInstance();
        $session->start();
        
        MessageHelper::addMessage('Test message 1');
        
        $messages = $session->get('messages');
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertSame('Test message 1', $messages[0]);
    }

    public function testAddMessageAppendsToExisting(): void
    {
        $session = SessionManager::getInstance();
        $session->start();
        
        MessageHelper::addMessage('First message');
        MessageHelper::addMessage('Second message');
        
        $messages = $session->get('messages');
        $this->assertCount(2, $messages);
        $this->assertSame('First message', $messages[0]);
        $this->assertSame('Second message', $messages[1]);
    }

    public function testDisplayAndClearMessagesOutputsJavascript(): void
    {
        $session = SessionManager::getInstance();
        $session->start();
        
        MessageHelper::addMessage('Test notification');
        MessageHelper::addMessage('Another notification');
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<script>showToast(', $output);
        $this->assertStringContainsString('Test notification', $output);
        $this->assertStringContainsString('Another notification', $output);
    }

    public function testDisplayAndClearMessagesClearsSession(): void
    {
        $session = SessionManager::getInstance();
        $session->start();
        
        MessageHelper::addMessage('Message to clear');
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        ob_end_clean();
        
        $messages = $session->get('messages');
        $this->assertSame([], $messages);
    }

    public function testDisplayAndClearMessagesHandlesNoMessages(): void
    {
        $session = SessionManager::getInstance();
        $session->start();
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        $output = ob_get_clean();
        
        $this->assertSame('', $output);
    }

    public function testDisplayAndClearMessagesHandlesEmptyArray(): void
    {
        $session = SessionManager::getInstance();
        $session->start();
        $session->set('messages', []);
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        $output = ob_get_clean();
        
        $this->assertSame('', $output);
    }

    public function testMessagesAreJsonEncoded(): void
    {
        $session = SessionManager::getInstance();
        $session->start();
        
        MessageHelper::addMessage('Message with "quotes"');
        
        ob_start();
        MessageHelper::displayAndClearMessages();
        $output = ob_get_clean();
        
        // Should contain properly JSON-encoded string
        $this->assertStringContainsString('Message with \"quotes\"', $output);
    }
}
