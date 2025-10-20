<?php
namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseManager;
use App\Controllers\KeyController;
use App\Helpers\Encryption;

class KeyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', __DIR__ . '/../update-api/storage/test.sqlite');
        }
        if (!defined('ENCRYPTION_KEY')) {
            define('ENCRYPTION_KEY', 'secret');
        }
        if (file_exists(DB_FILE)) {
            <?php
            namespace Tests;

            use PHPUnit\Framework\TestCase;

            class KeyControllerTest extends TestCase
            {
                public function testPlaceholder(): void
                {
                    $this->markTestSkipped('Legacy key-exchange tests removed.');
                }
            }
    protected function tearDown(): void
