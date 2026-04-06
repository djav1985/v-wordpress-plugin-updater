<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

if (!function_exists(__NAMESPACE__ . '\\add_query_arg')) {
    function add_query_arg($args, $url)
    {
        return $url . '?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986);
    }
}

class UpdaterEncodingTest extends TestCase
{
    public function testAddQueryArgEncodesOnce(): void
    {
        $args = [
            'type'    => 'plugin',
            'domain'  => 'example domain',
            'slug'    => 'my plugin',
            'version' => '1.0 / beta',
            'key'     => 'abc',
        ];

        $url = add_query_arg($args, 'https://api.example.com');

        $this->assertStringContainsString('domain=example%20domain', $url);
        $this->assertStringContainsString('slug=my%20plugin', $url);
        $this->assertStringContainsString('version=1.0%20%2F%20beta', $url);
        $this->assertStringNotContainsString('my%2520plugin', $url);
        $this->assertStringNotContainsString('example%2520domain', $url);
        $this->assertStringNotContainsString('1.0%2520%252F%2520beta', $url);
    }

    public function testMainPluginEntryHasPluginHeader(): void
    {
        $file = __DIR__ . '/../v-wp-updater/v-wp-updater.php';
        $content = file_get_contents($file);
        $this->assertStringContainsString('Plugin Name:', $content);
        $this->assertStringNotContainsString('Theme Name:', $content);
    }

    public function testUpdaterServiceFilesExist(): void
    {
        $pluginUpdater = __DIR__ . '/../v-wp-updater/services/PluginUpdater.php';
        $themeUpdater = __DIR__ . '/../v-wp-updater/services/ThemeUpdater.php';

        $this->assertFileExists($pluginUpdater);
        $this->assertFileExists($themeUpdater);
    }

    public function testAddQueryArgReservedCharactersEncodeOnce(): void
    {
        $args = ['slug' => 'a+b/c', 'version' => '1.0+b/c'];
        $url = add_query_arg($args, 'https://api.example.com');
        $this->assertStringContainsString('slug=a%2Bb%2Fc', $url);
        $this->assertStringContainsString('version=1.0%2Bb%2Fc', $url);
        $this->assertStringNotContainsString('a%252Bb%252Fc', $url);
        $this->assertStringNotContainsString('1.0%252Bb%252Fc', $url);
    }

    public function testLifecycleHooksUseGuardedCleanupCalls(): void
    {
        $file = __DIR__ . '/../v-wp-updater/v-wp-updater.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString('file_exists( $uninstall_file )', $content);
        $this->assertStringContainsString("function_exists( 'vwpu_clear_plugin_update_schedule' )", $content);
        $this->assertStringContainsString("function_exists( 'vwpu_clear_theme_update_schedule' )", $content);
        $this->assertStringContainsString("function_exists( 'vwpu_uninstall_cleanup' )", $content);
    }
}
