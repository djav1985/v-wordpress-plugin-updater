<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseManager;
use App\Helpers\CronHelper;

class CronHelperTest extends TestCase
{
    private string $testPluginsDir;
    private string $testThemesDir;

    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/test-cronhelper.sqlite');
        }
        
        $this->testPluginsDir = sys_get_temp_dir() . '/test-plugins-cron';
        $this->testThemesDir = sys_get_temp_dir() . '/test-themes-cron';
        
        if (!is_dir($this->testPluginsDir)) {
            mkdir($this->testPluginsDir, 0777, true);
        }
        if (!is_dir($this->testThemesDir)) {
            mkdir($this->testThemesDir, 0777, true);
        }
        
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        
        $ref = new \ReflectionClass(DatabaseManager::class);
        $prop = $ref->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('CREATE TABLE plugins (slug TEXT PRIMARY KEY, version TEXT)');
        $conn->executeStatement('CREATE TABLE themes (slug TEXT PRIMARY KEY, version TEXT)');
        $conn->executeStatement(
            'CREATE TABLE blacklist (ip TEXT PRIMARY KEY, login_attempts INTEGER, blacklisted INTEGER, timestamp INTEGER)'
        );
    }

    protected function tearDown(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DROP TABLE IF EXISTS plugins');
        $conn->executeStatement('DROP TABLE IF EXISTS themes');
        $conn->executeStatement('DROP TABLE IF EXISTS blacklist');
        
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        
        array_map('unlink', glob($this->testPluginsDir . '/*.zip'));
        array_map('unlink', glob($this->testThemesDir . '/*.zip'));
        if (is_dir($this->testPluginsDir)) {
            rmdir($this->testPluginsDir);
        }
        if (is_dir($this->testThemesDir)) {
            rmdir($this->testThemesDir);
        }
    }

    public function testSyncDirInsertsNewPlugins(): void
    {
        // Create test plugin files
        file_put_contents($this->testPluginsDir . '/my-plugin_1.0.zip', 'data');
        file_put_contents($this->testPluginsDir . '/another-plugin_2.0.zip', 'data');
        
        $conn = DatabaseManager::getConnection();
        CronHelper::syncDir($this->testPluginsDir, 'plugins', $conn);
        
        $plugins = $conn->fetchAllAssociative('SELECT * FROM plugins ORDER BY slug');
        $this->assertCount(2, $plugins);
        $this->assertSame('another-plugin', $plugins[0]['slug']);
        $this->assertSame('2.0', $plugins[0]['version']);
        $this->assertSame('my-plugin', $plugins[1]['slug']);
        $this->assertSame('1.0', $plugins[1]['version']);
    }

    public function testSyncDirUpdatesExistingPlugins(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->insert('plugins', ['slug' => 'my-plugin', 'version' => '1.0']);
        
        // Create newer version
        file_put_contents($this->testPluginsDir . '/my-plugin_2.0.zip', 'data');
        
        CronHelper::syncDir($this->testPluginsDir, 'plugins', $conn);
        
        $plugin = $conn->fetchAssociative('SELECT * FROM plugins WHERE slug = ?', ['my-plugin']);
        $this->assertSame('2.0', $plugin['version']);
    }

    public function testSyncDirRemovesOrphanedRecords(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->insert('plugins', ['slug' => 'old-plugin', 'version' => '1.0']);
        $conn->insert('plugins', ['slug' => 'another-plugin', 'version' => '1.0']);
        
        // Only create file for 'another-plugin'
        file_put_contents($this->testPluginsDir . '/another-plugin_1.0.zip', 'data');
        
        CronHelper::syncDir($this->testPluginsDir, 'plugins', $conn);
        
        $plugins = $conn->fetchAllAssociative('SELECT * FROM plugins');
        $this->assertCount(1, $plugins);
        $this->assertSame('another-plugin', $plugins[0]['slug']);
    }

    public function testSyncDirIgnoresInvalidFilenames(): void
    {
        // Create files with invalid naming
        file_put_contents($this->testPluginsDir . '/invalid.zip', 'data');
        file_put_contents($this->testPluginsDir . '/no-version.zip', 'data');
        file_put_contents($this->testPluginsDir . '/valid-plugin_1.0.zip', 'data');
        
        $conn = DatabaseManager::getConnection();
        CronHelper::syncDir($this->testPluginsDir, 'plugins', $conn);
        
        $plugins = $conn->fetchAllAssociative('SELECT * FROM plugins');
        $this->assertCount(1, $plugins);
        $this->assertSame('valid-plugin', $plugins[0]['slug']);
    }

    public function testCleanupBlacklistRemovesExpiredBlacklisted(): void
    {
        $conn = DatabaseManager::getConnection();
        
        // Add a blacklisted IP that's more than 7 days old
        $conn->insert('blacklist', [
            'ip' => '10.0.0.1',
            'login_attempts' => 3,
            'blacklisted' => 1,
            'timestamp' => time() - (8 * 24 * 60 * 60), // 8 days ago
        ]);
        
        // Add a recent blacklisted IP
        $conn->insert('blacklist', [
            'ip' => '10.0.0.2',
            'login_attempts' => 3,
            'blacklisted' => 1,
            'timestamp' => time() - (2 * 24 * 60 * 60), // 2 days ago
        ]);
        
        CronHelper::cleanupBlacklist($conn);
        
        $records = $conn->fetchAllAssociative('SELECT * FROM blacklist');
        $this->assertCount(1, $records);
        $this->assertSame('10.0.0.2', $records[0]['ip']);
    }

    public function testCleanupBlacklistRemovesExpiredNonBlacklisted(): void
    {
        $conn = DatabaseManager::getConnection();
        
        // Add a non-blacklisted IP that's more than 3 days old
        $conn->insert('blacklist', [
            'ip' => '10.0.0.3',
            'login_attempts' => 2,
            'blacklisted' => 0,
            'timestamp' => time() - (4 * 24 * 60 * 60), // 4 days ago
        ]);
        
        // Add a recent non-blacklisted IP
        $conn->insert('blacklist', [
            'ip' => '10.0.0.4',
            'login_attempts' => 1,
            'blacklisted' => 0,
            'timestamp' => time() - (1 * 24 * 60 * 60), // 1 day ago
        ]);
        
        CronHelper::cleanupBlacklist($conn);
        
        $records = $conn->fetchAllAssociative('SELECT * FROM blacklist');
        $this->assertCount(1, $records);
        $this->assertSame('10.0.0.4', $records[0]['ip']);
    }

    public function testSyncDirWorksWithThemes(): void
    {
        file_put_contents($this->testThemesDir . '/my-theme_1.0.zip', 'data');
        
        $conn = DatabaseManager::getConnection();
        CronHelper::syncDir($this->testThemesDir, 'themes', $conn);
        
        $themes = $conn->fetchAllAssociative('SELECT * FROM themes');
        $this->assertCount(1, $themes);
        $this->assertSame('my-theme', $themes[0]['slug']);
        $this->assertSame('1.0', $themes[0]['version']);
    }
}
