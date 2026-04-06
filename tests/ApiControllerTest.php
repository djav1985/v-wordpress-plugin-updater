<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Controllers\ApiController;
use App\Core\DatabaseManager;
use App\Core\ResponseManager;

/**
 * Tests for ApiController::handleRequest() covering request validation,
 * authentication, and response status codes / payload shape.
 */
class ApiControllerTest extends TestCase
{
    /** @var \Doctrine\DBAL\Connection */
    private $conn;

    /**
     * Initialize ErrorManager exactly once for the entire test class so that
     * error/exception/shutdown handlers are registered only once, avoiding
     * the accumulation of shutdown handlers that cannot be un-registered.
     */
    public static function setUpBeforeClass(): void
    {
        \App\Core\ErrorManager::getInstance();
    }

    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/test-api-controller.sqlite');
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }

        $ref  = new \ReflectionClass(DatabaseManager::class);
        $prop = $ref->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->conn = DatabaseManager::getConnection();

        $this->conn->executeStatement(
            'CREATE TABLE blacklist (ip TEXT PRIMARY KEY, login_attempts INTEGER, blacklisted INTEGER, timestamp INTEGER)'
        );
        $this->conn->executeStatement(
            'CREATE TABLE hosts (domain TEXT PRIMARY KEY, key TEXT NOT NULL)'
        );
        $this->conn->executeStatement(
            'CREATE TABLE plugins (slug TEXT PRIMARY KEY, version TEXT NOT NULL)'
        );
        $this->conn->executeStatement(
            'CREATE TABLE themes (slug TEXT PRIMARY KEY, version TEXT NOT NULL)'
        );
        $this->conn->executeStatement(
            'CREATE TABLE logs (id INTEGER PRIMARY KEY AUTOINCREMENT, domain TEXT, type TEXT, date TEXT, status TEXT)'
        );

        if (!defined('PLUGINS_DIR')) {
            define('PLUGINS_DIR', sys_get_temp_dir());
        }
        if (!defined('THEMES_DIR')) {
            define('THEMES_DIR', sys_get_temp_dir());
        }
        if (!defined('LOG_FILE')) {
            define('LOG_FILE', sys_get_temp_dir() . '/test-api-controller.log');
        }
        if (!defined('ENCRYPTION_KEY')) {
            define('ENCRYPTION_KEY', bin2hex(random_bytes(32)));
        }

        $_SERVER['REMOTE_ADDR']     = '127.0.0.1';
        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_GET = [];
    }

    protected function tearDown(): void
    {
        foreach (['blacklist', 'hosts', 'plugins', 'themes', 'logs'] as $table) {
            $this->conn->executeStatement("DROP TABLE IF EXISTS $table");
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        if (defined('LOG_FILE') && file_exists(LOG_FILE)) {
            unlink(LOG_FILE);
        }
        $_GET = [];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function validGetParams(string $type = 'plugin'): array
    {
        return [
            'type'    => $type,
            'domain'  => 'example.com',
            'key'     => 'validkey123',
            'slug'    => 'my-plugin',
            'version' => '1.0.0',
        ];
    }

    private function dispatch(): ResponseManager
    {
        $controller = new ApiController();
        return $controller->handleRequest();
    }

    private function getBlacklistAttempts(string $ip = '127.0.0.1'): int
    {
        $attempts = $this->conn->fetchOne('SELECT login_attempts FROM blacklist WHERE ip = ?', [$ip]);
        return $attempts === false ? 0 : (int) $attempts;
    }

    // ------------------------------------------------------------------
    // Non-GET method
    // ------------------------------------------------------------------

    public function testPostMethodReturnsMethodNotAllowed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = $this->validGetParams();

        $response = $this->dispatch();
        $this->assertSame(405, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // Missing / invalid parameters → 400
    // ------------------------------------------------------------------

    public function testMissingTypeReturns400(): void
    {
        $params = $this->validGetParams();
        unset($params['type']);
        $_GET = $params;

        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testInvalidTypeReturns400(): void
    {
        $_GET = array_merge($this->validGetParams(), ['type' => 'unknown']);
        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testMissingDomainReturns400(): void
    {
        $params = $this->validGetParams();
        unset($params['domain']);
        $_GET = $params;

        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testMissingKeyReturns400(): void
    {
        $params = $this->validGetParams();
        unset($params['key']);
        $_GET = $params;

        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testMissingSlugReturns400(): void
    {
        $params = $this->validGetParams();
        unset($params['slug']);
        $_GET = $params;

        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testMissingVersionReturns400(): void
    {
        $params = $this->validGetParams();
        unset($params['version']);
        $_GET = $params;

        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testInvalidDomainReturns400(): void
    {
        $_GET = array_merge($this->validGetParams(), ['domain' => 'not a domain!']);
        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testInvalidSlugReturns400(): void
    {
        $_GET = array_merge($this->validGetParams(), ['slug' => '']);
        $this->assertSame(400, $this->dispatch()->getStatusCode());
    }

    public function testMalformedRequestDoesNotConsumeBlacklistBudget(): void
    {
        $_GET = array_merge($this->validGetParams(), ['slug' => 'invalid slug']);

        $this->assertSame(400, $this->dispatch()->getStatusCode());
        $this->assertSame(0, $this->getBlacklistAttempts());
    }

    // ------------------------------------------------------------------
    // Auth failure → 403
    // ------------------------------------------------------------------

    public function testUnknownDomainReturnsForbidden(): void
    {
        $_GET = $this->validGetParams();
        // No host row in DB → 403
        $this->assertSame(403, $this->dispatch()->getStatusCode());
    }

    public function testWrongKeyReturnsForbidden(): void
    {
        // Insert host with a different key
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('correctkey');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );
        $_GET = array_merge($this->validGetParams(), ['key' => 'wrongkey']);

        $this->assertSame(403, $this->dispatch()->getStatusCode());
        $this->assertSame(1, $this->getBlacklistAttempts());
    }

    // ------------------------------------------------------------------
    // No update available → 204
    // ------------------------------------------------------------------

    public function testSameVersionReturns204(): void
    {
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('validkey123');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );
        $this->conn->executeStatement(
            'INSERT INTO plugins (slug, version) VALUES (?, ?)',
            ['my-plugin', '1.0.0']
        );

        $_GET = $this->validGetParams(); // version 1.0.0, DB has 1.0.0
        $this->assertSame(204, $this->dispatch()->getStatusCode());
    }

    public function testNewerInstalledVersionReturns204(): void
    {
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('validkey123');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );
        $this->conn->executeStatement(
            'INSERT INTO plugins (slug, version) VALUES (?, ?)',
            ['my-plugin', '0.9.0']
        );

        $_GET = array_merge($this->validGetParams(), ['version' => '1.0.0']); // client is newer
        $this->assertSame(204, $this->dispatch()->getStatusCode());
    }

    public function testAuthenticatedUnknownSlugReturns404WithoutBlacklistIncrement(): void
    {
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('validkey123');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );

        $_GET = array_merge($this->validGetParams(), ['slug' => 'missing-plugin']);

        $this->assertSame(404, $this->dispatch()->getStatusCode());
        $this->assertSame(0, $this->getBlacklistAttempts());
    }

    // ------------------------------------------------------------------
    // Update available + ZIP present → 200 with file response
    // ------------------------------------------------------------------

    public function testUpdateAvailableReturns200WithZip(): void
    {
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('validkey123');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );
        $this->conn->executeStatement(
            'INSERT INTO plugins (slug, version) VALUES (?, ?)',
            ['my-plugin', '2.0.0']
        );

        // Create a dummy ZIP file in PLUGINS_DIR
        $zipPath = PLUGINS_DIR . '/my-plugin_2.0.0.zip';
        file_put_contents($zipPath, 'FAKE_ZIP_CONTENT');

        $_GET = $this->validGetParams(); // client has 1.0.0, DB has 2.0.0

        $response = $this->dispatch();

        // Clean up
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $this->assertSame(200, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // theme type
    // ------------------------------------------------------------------

    public function testThemeTypeReturns204WhenNoUpdate(): void
    {
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('validkey123');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );
        $this->conn->executeStatement(
            'INSERT INTO themes (slug, version) VALUES (?, ?)',
            ['my-theme', '1.0.0']
        );

        $_GET = $this->validGetParams('theme');
        $_GET['slug'] = 'my-theme';

        $this->assertSame(204, $this->dispatch()->getStatusCode());
    }

    public function testThemeTypeUpdateAvailableReturns200(): void
    {
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('validkey123');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );
        $this->conn->executeStatement(
            'INSERT INTO themes (slug, version) VALUES (?, ?)',
            ['my-theme', '3.0.0']
        );

        $zipPath = THEMES_DIR . '/my-theme_3.0.0.zip';
        file_put_contents($zipPath, 'FAKE_ZIP');

        $_GET = $this->validGetParams('theme');
        $_GET['slug']    = 'my-theme';
        $_GET['version'] = '2.0.0';

        $response = $this->dispatch();

        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateAvailableWithMissingFileReturns500(): void
    {
        $encrypted = \App\Helpers\EncryptionHelper::encrypt('validkey123');
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', $encrypted]
        );
        $this->conn->executeStatement(
            'INSERT INTO plugins (slug, version) VALUES (?, ?)',
            ['my-plugin', '2.0.0']
        );

        $_GET = $this->validGetParams();
        $this->assertSame(500, $this->dispatch()->getStatusCode());
    }

    // ------------------------------------------------------------------
    // Blacklisted IP → 403
    // ------------------------------------------------------------------

    public function testBlacklistedIpReturnsForbidden(): void
    {
        $this->conn->executeStatement(
            'INSERT INTO blacklist (ip, login_attempts, blacklisted, timestamp) VALUES (?, ?, ?, ?)',
            ['127.0.0.1', 3, 1, time()]
        );

        $_GET = $this->validGetParams();
        $this->assertSame(403, $this->dispatch()->getStatusCode());
    }
}
