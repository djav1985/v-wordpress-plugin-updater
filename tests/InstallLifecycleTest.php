<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

class InstallLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        global $__vontmnt_test_storage;
        $__vontmnt_test_storage['options'] = [];
        $__vontmnt_test_storage['scheduled'] = [];

        require_once __DIR__ . '/../v-wp-updater/helpers/Logger.php';
        require_once __DIR__ . '/../v-wp-updater/helpers/Options.php';
        require_once __DIR__ . '/../v-wp-updater/install.php';
        require_once __DIR__ . '/../v-wp-updater/uninstall.php';
    }

    public function testInstallMigratesLegacyStatusOptionKeys(): void
    {
        update_option('vontmnt-plup', true);
        update_option('vwpu-thup', false);

        \vwpu_install();

        $this->assertTrue((bool) get_option('vwpu_plugin_update_status', false));
        $this->assertFalse((bool) get_option('vwpu_theme_update_status', true));
        $this->assertSame('missing', get_option('vontmnt-plup', 'missing'));
        $this->assertSame('missing', get_option('vwpu-thup', 'missing'));
    }

    public function testUninstallCleanupDeletesCanonicalAndLegacyStatusKeys(): void
    {
        update_option('vwpu_plugin_update_status', true);
        update_option('vwpu_theme_update_status', true);
        update_option('vwpu-plup', true);
        update_option('vontmnt-thup', true);

        \vwpu_uninstall_cleanup();

        $this->assertSame('missing', get_option('vwpu_plugin_update_status', 'missing'));
        $this->assertSame('missing', get_option('vwpu_theme_update_status', 'missing'));
        $this->assertSame('missing', get_option('vwpu-plup', 'missing'));
        $this->assertSame('missing', get_option('vontmnt-thup', 'missing'));
    }
}
