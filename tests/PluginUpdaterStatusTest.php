<?php

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', sys_get_temp_dir() . '/');
    }

    if (!function_exists('wp_remote_get')) {
        function wp_remote_get(...$args)
        {
            return ['code' => 204, 'body' => ''];
        }
    }
    if (!function_exists('wp_remote_retrieve_response_code')) {
        function wp_remote_retrieve_response_code($response)
        {
            return (int) ($response['code'] ?? 0);
        }
    }
    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing)
        {
            return false;
        }
    }
    if (!function_exists('site_url')) {
        function site_url()
        {
            return 'https://example.com';
        }
    }
    if (!function_exists('wp_parse_url')) {
        function wp_parse_url($url, $component = -1)
        {
            return parse_url($url, $component);
        }
    }
    if (!function_exists('add_query_arg')) {
        function add_query_arg($args, $url)
        {
            return $url . '?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986);
        }
    }
    if (!function_exists('__')) {
        function __($text, $domain = '')
        {
            return $text;
        }
    }
    if (!class_exists('WP_Upgrader_Skin')) {
        class WP_Upgrader_Skin
        {
            public $errors = [];
            public function __construct($args = [])
            {
            }
        }
    }

    require_once __DIR__ . '/../v-wp-updater/helpers/Options.php';
    require_once __DIR__ . '/../v-wp-updater/helpers/SilentUpgraderSkin.php';
    require_once __DIR__ . '/../v-wp-updater/helpers/AbstractRemoteUpdater.php';
    require_once __DIR__ . '/../v-wp-updater/services/PluginUpdater.php';
}

namespace Tests {

use PHPUnit\Framework\TestCase;

class TestablePluginUpdaterForStatus extends \VWPU\Services\PluginUpdater
{
    /**
     * @return array<int, array{slug: string, version: string, file_path: string}>
     */
    public function exposedEnumerateInstalledItems(): array
    {
        return iterator_to_array($this->enumerate_installed_items(), false);
    }
}

class PluginUpdaterStatusTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['__wp_plugins'] = [];
        $GLOBALS['options'] = [
            'vwpu_update_key' => 'test-key',
            'vwpu_update_plugin_url' => 'https://api.example.com',
        ];
    }

    public function testEnumerateInstalledItemsUsesFilenameSlugForSingleFilePlugin(): void
    {
        $GLOBALS['__wp_plugins'] = [
            'single-file-plugin.php' => ['Version' => '1.0.0'],
        ];

        $updater = new TestablePluginUpdaterForStatus();
        $items = $updater->exposedEnumerateInstalledItems();

        $this->assertCount(1, $items);
        $this->assertSame('single-file-plugin', $items[0]['slug']);
        $this->assertSame('single-file-plugin.php', $items[0]['file_path']);
    }

    public function testEnumerateInstalledItemsUsesDirectorySlugForDirectoryPlugins(): void
    {
        $GLOBALS['__wp_plugins'] = [
            'sample-plugin/sample-plugin.php' => ['Version' => '2.5.0'],
        ];

        $updater = new TestablePluginUpdaterForStatus();
        $items = $updater->exposedEnumerateInstalledItems();

        $this->assertCount(1, $items);
        $this->assertSame('sample-plugin', $items[0]['slug']);
        $this->assertSame('sample-plugin/sample-plugin.php', $items[0]['file_path']);
    }
}

}
