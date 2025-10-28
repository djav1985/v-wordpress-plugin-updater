<?php

namespace Tests {

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseManager;
use App\Models\ThemeModel;

class ThemeModelDbTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/test-themes.sqlite');
        }
        if (!defined('THEMES_DIR')) {
            define('THEMES_DIR', sys_get_temp_dir() . '/themes');
        }
        if (!is_dir(THEMES_DIR)) {
            mkdir(THEMES_DIR, 0777, true);
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        $ref = new \ReflectionClass(DatabaseManager::class);
        $prop = $ref->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('CREATE TABLE themes (slug TEXT PRIMARY KEY, version TEXT)');
    }

    protected function tearDown(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DROP TABLE IF EXISTS themes');
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        array_map('unlink', glob(THEMES_DIR . '/*.zip'));
    }

    public function testUploadValidZipInsertsRecord(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'th');
        file_put_contents($tmp, 'data');
        $files = [
            'name'     => ['sample-theme_1.0.zip'],
            'tmp_name' => [$tmp],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [filesize($tmp)],
        ];
        $messages = ThemeModel::uploadFiles($files);
        $this->assertStringContainsString('uploaded successfully', $messages[0]);
        $conn = DatabaseManager::getConnection();
        $row = $conn->fetchAssociative('SELECT * FROM themes WHERE slug = ?', ['sample-theme']);
        $this->assertSame('1.0', $row['version']);
    }

    public function testUploadFileTooLargeReturnsError(): void
    {
        global $test_ini_values;
        $test_ini_values = [
            'upload_max_filesize' => '1K',
            'post_max_size' => '1K',
        ];
        $tmp = tempnam(sys_get_temp_dir(), 'th');
        file_put_contents($tmp, str_repeat('a', 2048));
        $files = [
            'name'     => ['big-theme_1.0.zip'],
            'tmp_name' => [$tmp],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [filesize($tmp)],
        ];
        $messages = ThemeModel::uploadFiles($files);
        $this->assertStringContainsString('File size exceeds', $messages[0]);
        $test_ini_values = [];
    }

    public function testUploadNonZipReturnsError(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'th');
        file_put_contents($tmp, 'data');
        $files = [
            'name'     => ['bad.txt'],
            'tmp_name' => [$tmp],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [filesize($tmp)],
        ];
        $messages = ThemeModel::uploadFiles($files);
        $this->assertStringContainsString('Only .zip files are allowed', $messages[0]);
    }

    public function testDeleteThemeReturnsFalseForInvalidFile(): void
    {
        $this->assertFalse(ThemeModel::deleteTheme('missing.zip'));

        $outsideDir = sys_get_temp_dir() . '/outside';
        if (!is_dir($outsideDir)) {
            mkdir($outsideDir);
        }
        $outside = $outsideDir . '/evil.zip';
        file_put_contents($outside, 'data');
        symlink($outside, THEMES_DIR . '/evil.zip');
        $this->assertFalse(ThemeModel::deleteTheme('evil.zip'));
        unlink(THEMES_DIR . '/evil.zip');
        unlink($outside);
    }

    public function testGetThemesReturnsArray(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->insert('themes', ['slug' => 'theme1', 'version' => '1.0']);
        $conn->insert('themes', ['slug' => 'theme2', 'version' => '2.0']);
        
        $themes = ThemeModel::getThemes();
        $this->assertCount(2, $themes);
        $this->assertSame('theme1', $themes[0]['slug']);
        $this->assertSame('1.0', $themes[0]['version']);
    }
}

}
