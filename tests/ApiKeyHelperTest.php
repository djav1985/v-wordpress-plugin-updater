<?php
namespace {
    if (!class_exists('WP_Error')) {
        class WP_Error {
            private string $message;
            public function __construct($c, $m) { $this->message = $m; }
            public function get_error_message() { return $this->message; }
        }
    }
    if (!function_exists('add_action')) { function add_action(...$args) {} }
    if (!function_exists('wp_next_scheduled')) { function wp_next_scheduled($hook) { return false; } }
    if (!function_exists('wp_schedule_event')) { function wp_schedule_event($timestamp, $recurrence, $hook) {} }
    if (!function_exists('get_plugins')) { function get_plugins() { return []; } }
    if (!function_exists('wp_parse_url')) { function wp_parse_url($url, $component) { return 'example.com'; } }
    if (!function_exists('site_url')) { function site_url() { return 'https://example.com'; } }
    if (!function_exists('add_query_arg')) { function add_query_arg($args, $url) { return rtrim($url, '/') . '/key?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986); } }
    $options = [];
    if (!function_exists('get_option')) { function get_option($name) { global $options; return $options[$name] ?? false; } }
    if (!function_exists('update_option')) { function update_option($name, $value) { global $options; $options[$name] = $value; return true; } }
    $remote_calls = 0;
    if (!function_exists('wp_remote_get')) { function wp_remote_get($url) { global $remote_calls; $remote_calls++; return ['body' => 'secret', 'response' => ['code' => 200]]; } }
    if (!function_exists('wp_remote_retrieve_response_code')) { function wp_remote_retrieve_response_code($response) { return $response['response']['code']; } }
    if (!function_exists('wp_remote_retrieve_body')) { function wp_remote_retrieve_body($response) { return $response['body']; } }
    if (!function_exists('wp_get_themes')) { function wp_get_themes() { return []; } }
    if (!function_exists('wp_upload_dir')) { function wp_upload_dir() { return ['path' => sys_get_temp_dir()]; } }
    if (!function_exists('wp_delete_file')) { function wp_delete_file($file) {} }
    if (!function_exists('add_filter')) { function add_filter(...$args) {} }
    if (!function_exists('remove_filter')) { function remove_filter(...$args) {} }
    if (!function_exists('is_main_site')) { function is_main_site() { return true; } }
    if (!function_exists('is_wp_error')) { function is_wp_error($thing) { return $thing instanceof WP_Error; } }
}

namespace Tests {
use PHPUnit\Framework\TestCase;

class ApiKeyHelperTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', sys_get_temp_dir() . '/');
        }
        file_put_contents(ABSPATH . 'wp-config.php', "<?php\ndefine('VONTMNT_UPDATE_KEYREGEN', true);\n");
        if (!defined('VONTMENT_PLUGINS')) {
            define('VONTMENT_PLUGINS', 'https://example.com/api');
        }
    }

    public function testOptionPersistence(): void
    {
        global $remote_calls;
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        $key1 = \vontmnt_get_api_key();
        $this->assertSame('secret', $key1);
        $this->assertSame(1, $remote_calls);
        $content = file_get_contents(ABSPATH . 'wp-config.php');
        $this->assertStringContainsString("VONTMNT_UPDATE_KEYREGEN', false", $content);
        $key2 = \vontmnt_get_api_key();
        $this->assertSame('secret', $key2);
        $this->assertSame(1, $remote_calls);
    }
}
}
