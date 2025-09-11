<?php

namespace App\Models {
    function move_uploaded_file($from, $to)
    {
        return copy($from, $to);
    }
}

namespace Tests {

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

    public function testUploadValidZipInsertsRecord(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pl');
        file_put_contents($tmp, 'data');
        $files = [
            'name'     => ['sample_1.0.zip'],
            'tmp_name' => [$tmp],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [filesize($tmp)],
        ];
        $messages = PluginModel::uploadFiles($files);
        $this->assertStringContainsString('uploaded successfully', $messages[0]);
        $conn = DatabaseManager::getConnection();
        $row = $conn->fetchAssociative('SELECT * FROM plugins WHERE slug = ?', ['sample']);
        $this->assertSame('1.0', $row['version']);
    }

    public function testUploadFileTooLargeReturnsError(): void
    {
        $oldUpload = ini_set('upload_max_filesize', '1K');
        $oldPost   = ini_set('post_max_size', '1K');
        $tmp = tempnam(sys_get_temp_dir(), 'pl');
        file_put_contents($tmp, str_repeat('a', 2048));
        $files = [
            'name'     => ['big_1.0.zip'],
            'tmp_name' => [$tmp],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [filesize($tmp)],
        ];
        $messages = PluginModel::uploadFiles($files);
        $this->assertStringContainsString('File size exceeds', $messages[0]);
        ini_set('upload_max_filesize', $oldUpload);
        ini_set('post_max_size', $oldPost);
    }

    public function testUploadNonZipReturnsError(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pl');
        file_put_contents($tmp, 'data');
        $files = [
            'name'     => ['bad.txt'],
            'tmp_name' => [$tmp],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [filesize($tmp)],
        ];
        $messages = PluginModel::uploadFiles($files);
        $this->assertStringContainsString('Only .zip files are allowed', $messages[0]);
    }

    public function testDeletePluginReturnsFalseForInvalidFile(): void
    {
        $this->assertFalse(PluginModel::deletePlugin('missing.zip'));

        $outsideDir = sys_get_temp_dir() . '/outside';
        if (!is_dir($outsideDir)) {
            mkdir($outsideDir);
        }
        $outside = $outsideDir . '/evil.zip';
        file_put_contents($outside, 'data');
        symlink($outside, PLUGINS_DIR . '/evil.zip');
        $this->assertFalse(PluginModel::deletePlugin('evil.zip'));
        unlink(PLUGINS_DIR . '/evil.zip');
        unlink($outside);
    }
}

}
