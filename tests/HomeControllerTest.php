<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use App\Controllers\HomeController;
use App\Core\DatabaseManager;
use App\Core\ResponseManager;
use App\Core\SessionManager;
use App\Helpers\EncryptionHelper;
use PHPUnit\Framework\TestCase;

class HomeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/test-home-controller.sqlite');
        }
        if (!defined('ENCRYPTION_KEY')) {
            define('ENCRYPTION_KEY', 'home-controller-test-key');
        }

        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }

        $dbRef = new \ReflectionClass(DatabaseManager::class);
        $dbProp = $dbRef->getProperty('connection');
        $dbProp->setAccessible(true);
        $dbProp->setValue(null, null);

        $sessionRef = new \ReflectionClass(SessionManager::class);
        $sessionProp = $sessionRef->getProperty('instance');
        $sessionProp->setAccessible(true);
        $sessionProp->setValue(null, null);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        $_SERVER['HTTP_USER_AGENT'] = 'HomeControllerTestAgent';

        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('CREATE TABLE hosts (domain TEXT PRIMARY KEY, key TEXT NOT NULL)');
        $conn->executeStatement('CREATE TABLE logs (domain TEXT, type TEXT, date TEXT, status TEXT)');

        $conn->insert('hosts', [
            'domain' => 'example.com',
            'key' => EncryptionHelper::encrypt('plain-visible-key'),
        ]);

        $session = SessionManager::getInstance();
        $session->start();
        $session->set('csrf_token', 'home-test-token');
    }

    protected function tearDown(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DROP TABLE IF EXISTS logs');
        $conn->executeStatement('DROP TABLE IF EXISTS hosts');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
    }

    public function testHostKeysAreMaskedByDefaultInView(): void
    {
        $controller = new HomeController();
        $response = $controller->handleRequest();

        $this->assertInstanceOf(ResponseManager::class, $response);
        $data = $response->getViewData();
        $html = (string) ($data['hostsTableHtml'] ?? '');

        $this->assertStringNotContainsString('plain-visible-key', $html);
        $this->assertStringContainsString('data-masked-value=', $html);
        $this->assertStringContainsString('name="reveal_entry"', $html);
        $this->assertStringContainsString('copy-key-btn', $html);
    }

    public function testRevealSubmissionTemporarilyDisplaysKeyWithoutLeakingInMessage(): void
    {
        $_POST = [
            'csrf_token' => 'home-test-token',
            'domain' => 'example.com',
            'reveal_entry' => 'Reveal',
        ];

        $controller = new HomeController();
        $submissionResponse = $controller->handleSubmission();
        $this->assertInstanceOf(ResponseManager::class, $submissionResponse);

        $messages = SessionManager::getInstance()->get('messages', []);
        $this->assertIsArray($messages);
        $this->assertStringContainsString('Key revealed for 30 seconds.', (string) ($messages[0] ?? ''));
        $this->assertStringNotContainsString('plain-visible-key', (string) ($messages[0] ?? ''));

        $response = $controller->handleRequest();
        $data = $response->getViewData();
        $html = (string) ($data['hostsTableHtml'] ?? '');

        $this->assertStringContainsString('plain-visible-key', $html);
        $this->assertDoesNotMatchRegularExpression('/data-expires-at="0"/', $html);
    }

    public function testDeleteSubmissionFailureLogsUserMessage(): void
    {
        $_POST = [
            'csrf_token' => 'home-test-token',
            'domain' => 'missing.example.com',
            'delete_entry' => 'Delete',
        ];

        $controller = new HomeController();
        $controller->handleSubmission();

        $messages = SessionManager::getInstance()->get('messages', []);
        $this->assertIsArray($messages);
        $this->assertStringContainsString('Failed to delete entry.', implode(' ', $messages));
    }
}
