<?php
namespace {
    if (!class_exists('WP_Error')) {
        class WP_Error {
            private string $message;
            public function __construct(string $code, string $message) {
                $this->message = $message;
            }
            public function get_error_message(): string {
                return $this->message;
            }
        }
    }
    if (!function_exists('add_action')) { function add_action(...$args) {} }
    if (!function_exists('wp_next_scheduled')) { function wp_next_scheduled($hook) { return false; } }
    if (!function_exists('wp_schedule_event')) { function wp_schedule_event($timestamp, $recurrence, $hook) {} }
    if (!function_exists('get_plugins')) { function get_plugins() { return ['my-plugin/my-plugin.php' => ['Version' => '1.0.0']]; } }
    if (!function_exists('wp_parse_url')) { function wp_parse_url($url, $component) { return 'example.com'; } }
    if (!function_exists('site_url')) { function site_url() { return 'https://example.com'; } }
    if (!function_exists('add_query_arg')) { function add_query_arg($args, $url) { return $url . '?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986); } }
    if (!function_exists('wp_remote_get')) { function wp_remote_get($url) { return new WP_Error('error', 'network error'); } }
    $options = ['vontmnt_api_key' => 'key'];
    if (!function_exists('get_option')) { function get_option($name) { global $options; return $options[$name] ?? false; } }
    if (!function_exists('update_option')) { function update_option($name, $value) { global $options; $options[$name] = $value; return true; } }
    if (!function_exists('wp_get_themes')) { function wp_get_themes() { return [ new class { public function get_stylesheet() { return 'my-theme'; } public function get($field) { return '1.0.0'; } } ]; } }
    if (!function_exists('wp_upload_dir')) { function wp_upload_dir() { return ['path' => sys_get_temp_dir()]; } }
    if (!function_exists('wp_delete_file')) { function wp_delete_file($file) {} }
    if (!function_exists('add_filter')) { function add_filter(...$args) {} }
    if (!function_exists('remove_filter')) { function remove_filter(...$args) {} }
    if (!function_exists('is_main_site')) { function is_main_site() { return true; } }
    if (!function_exists('is_wp_error')) { function is_wp_error($thing) { return $thing instanceof WP_Error; } }
}

namespace Tests {

use PHPUnit\Framework\TestCase;

class UpdaterErrorHandlingTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'log');
        ini_set('error_log', $this->logFile);
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__ . '/../');
        }
        if (!defined('VONTMNT_API_URL')) {
            define('VONTMNT_API_URL', 'https://example.com/api');
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testPluginUpdaterHandlesWpError(): void
    {
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        $this->assertNull(vontmnt_plugin_updater_run_updates());
    }

    public function testThemeUpdaterHandlesWpError(): void
    {
        require_once __DIR__ . '/../mu-plugin/v-sys-theme-updater.php';
        $this->assertNull(vontmnt_theme_updater_run_updates());
    }
}


}
