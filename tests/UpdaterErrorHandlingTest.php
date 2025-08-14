<?php
namespace {
    class WP_Error {
        private string $message;
        public function __construct(string $code, string $message) {
            $this->message = $message;
        }
        public function get_error_message(): string {
            return $this->message;
        }
    }
    function add_action(...$args) {}
    function wp_next_scheduled($hook) { return false; }
    function wp_schedule_event($timestamp, $recurrence, $hook) {}
    function get_plugins() { return ['my-plugin/my-plugin.php' => ['Version' => '1.0.0']]; }
    function wp_parse_url($url, $component) { return 'example.com'; }
    function site_url() { return 'https://example.com'; }
    function add_query_arg($args, $url) { return $url . '?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986); }
    function wp_remote_get($url) { return new WP_Error('error', 'network error'); }
    function wp_get_themes() { return [ new class { public function get_stylesheet() { return 'my-theme'; } public function get($field) { return '1.0.0'; } } ]; }
    function wp_upload_dir() { return ['path' => sys_get_temp_dir()]; }
    function wp_delete_file($file) {}
    function add_filter(...$args) {}
    function remove_filter(...$args) {}
    function is_main_site() { return true; }
    function is_wp_error($thing) { return $thing instanceof WP_Error; }
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
        if (!defined('VONTMENT_KEY')) {
            define('VONTMENT_KEY', 'key');
        }
        if (!defined('VONTMENT_PLUGINS')) {
            define('VONTMENT_PLUGINS', 'https://example.com/plugins');
        }
        if (!defined('VONTMENT_THEMES')) {
            define('VONTMENT_THEMES', 'https://example.com/themes');
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
        vontmnt_plugin_updater_run_updates();
        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('Plugin updater error: network error', $log);
    }

    public function testThemeUpdaterHandlesWpError(): void
    {
        require_once __DIR__ . '/../mu-plugin/v-sys-theme-updater.php';
        vontmnt_theme_updater_run_updates();
        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('Theme updater error: network error', $log);
    }
}


}
