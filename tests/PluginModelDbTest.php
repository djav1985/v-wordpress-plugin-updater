<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseManager;
use App\Models\PluginModel;

class PluginModelDbTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/test.sqlite');
        }
        if (!defined('PLUGINS_DIR')) {
            define('PLUGINS_DIR', sys_get_temp_dir() . '/plugins');
        }
        if (!is_dir(PLUGINS_DIR)) {
            mkdir(PLUGINS_DIR, 0777, true);
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
    }

    protected function tearDown(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DROP TABLE IF EXISTS plugins');
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        array_map('unlink', glob(PLUGINS_DIR . '/*.zip'));
    }

    public function testUploadAndDeleteSyncsDatabase(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('INSERT INTO plugins (slug, version) VALUES (?, ?)', ['sample', '1.0']);
        $filePath = PLUGINS_DIR . '/sample_1.0.zip';
        file_put_contents($filePath, 'data');
        $plugins = PluginModel::getPlugins();
        $this->assertContains(['slug' => 'sample', 'version' => '1.0'], $plugins);
        $this->assertTrue(PluginModel::deletePlugin('sample_1.0.zip'));
        $row2 = $conn->fetchAssociative('SELECT * FROM plugins WHERE slug = ?', ['sample']);
        $this->assertFalse($row2);
    }
}
