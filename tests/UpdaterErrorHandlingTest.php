<?php
namespace {
    if (!class_exists('WP_Error')) {
        class WP_Error {
            public function __construct(private string $code, private string $message) {}
            public function get_error_message(): string { return $this->message; }
        }
    }
    $options = ['vontmnt_api_key' => 'key'];
    $plugins_list = ['my-plugin/my-plugin.php' => ['Version' => '1.0.0']];
    $themes_list = [ new class { public function get_stylesheet(){ return 'my-theme'; } public function get($f){ return '1.0.0'; } } ];
    $wp_remote_get_queue = [];
    $wp_remote_get_calls = 0;
    $wp_delete_file_calls = [];
    $logs = [];
    if (!function_exists('add_action')) { function add_action(...$a) {} }
    if (!function_exists('wp_next_scheduled')) { function wp_next_scheduled($h){ return false; } }
    if (!function_exists('wp_schedule_event')) { function wp_schedule_event($t,$r,$h) {} }
    if (!function_exists('get_plugins')) { function get_plugins(){ global $plugins_list; return $plugins_list; } }
    if (!function_exists('wp_parse_url')) { function wp_parse_url($u,$c){ return 'example.com'; } }
    if (!function_exists('site_url')) { function site_url(){ return 'https://example.com'; } }
    if (!function_exists('add_query_arg')) { function add_query_arg($args,$url){ return $url.'?'.http_build_query($args,'','&',PHP_QUERY_RFC3986); } }
    if (!function_exists('wp_remote_get')) { function wp_remote_get($url,$args=[]){
        global $wp_remote_get_queue,$wp_remote_get_calls;
        $wp_remote_get_calls++;
        $resp = array_shift($wp_remote_get_queue) ?? ['response'=>['code'=>200],'body'=>''];
        if (($args['stream'] ?? false) && isset($resp['body']) && isset($args['filename'])) {
            file_put_contents($args['filename'], $resp['body']);
        }
        return $resp;
    } }
    if (!function_exists('wp_remote_retrieve_response_code')) { function wp_remote_retrieve_response_code($resp){ return $resp['response']['code'] ?? 0; } }
    if (!function_exists('wp_remote_retrieve_body')) { function wp_remote_retrieve_body($resp){ return $resp['body'] ?? ''; } }
    if (!function_exists('get_option')) { function get_option($n){ global $options; return $options[$n] ?? false; } }
    if (!function_exists('update_option')) { function update_option($n,$v){ global $options; $options[$n] = $v; return true; } }
    if (!function_exists('wp_get_themes')) { function wp_get_themes(){ global $themes_list; return $themes_list; } }
    if (!function_exists('wp_upload_dir')) { function wp_upload_dir(){ return ['path'=>sys_get_temp_dir()]; } }
    if (!function_exists('wp_tempnam')) { function wp_tempnam($f){ return tempnam(sys_get_temp_dir(),'tmp'); } }
    if (!function_exists('wp_mkdir_p')) { function wp_mkdir_p($p){ return true; } }
    if (!function_exists('wp_delete_file')) { function wp_delete_file($f){ global $wp_delete_file_calls; $wp_delete_file_calls[] = $f; } }
    if (!function_exists('add_filter')) { function add_filter(...$a) {} }
    if (!function_exists('remove_filter')) { function remove_filter(...$a) {} }
    if (!function_exists('add_option')) { function add_option(...$a) {} }
    if (!function_exists('delete_option')) { function delete_option(...$a) {} }
    if (!function_exists('is_main_site')) { function is_main_site(){ return true; } }
    if (!function_exists('is_wp_error')) { function is_wp_error($t){ return $t instanceof WP_Error; } }
    if (!function_exists('WP_Filesystem')) { function WP_Filesystem(){
        global $wp_filesystem; $wp_filesystem = new class { public function move($f,$t){ rename($f,$t); return true; } }; return true;
    } }
    if (!class_exists('Plugin_Upgrader')) { class Plugin_Upgrader { public function install($f){ return true; } } }
    if (!class_exists('Theme_Upgrader')) { class Theme_Upgrader { public function install($f){ return true; } } }
    if (!function_exists('wp_clean_plugins_cache')) { function wp_clean_plugins_cache($f) {} }
    if (!function_exists('vontmnt_log_update_context')) { function vontmnt_log_update_context(...$a){ global $logs; $logs[]=$a; } }
}

namespace Tests {
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
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
        global $wp_remote_get_queue, $wp_remote_get_calls, $wp_delete_file_calls, $logs, $plugins_list, $themes_list;
        $wp_remote_get_queue = [];
        $wp_remote_get_calls = 0;
        $wp_delete_file_calls = [];
        $logs = [];
        $plugins_list = ['my-plugin/my-plugin.php' => ['Version' => '1.0.0']];
        $themes_list = [ new class { public function get_stylesheet(){ return 'my-theme'; } public function get($f){ return '1.0.0'; } } ];
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testPluginUpdaterInstallsAndCleans(): void
    {
        global $wp_remote_get_queue, $wp_delete_file_calls;
        $wp_remote_get_queue = [
            ['response'=>['code'=>200],'body'=>'zip'],
            ['response'=>['code'=>200],'body'=>'zip']
        ];
        if (!function_exists('vontmnt_plugin_updater_run_updates')) {
            require __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        }
        vontmnt_plugin_updater_run_updates();
        $this->assertNotEmpty($wp_delete_file_calls);
    }

    public function testPluginUpdaterHandlesWpError(): void
    {
        global $wp_remote_get_queue;
        $wp_remote_get_queue = [new \WP_Error('err', 'fail')];
        if (!function_exists('vontmnt_plugin_updater_run_updates')) {
            require __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        }
        $this->assertNull(vontmnt_plugin_updater_run_updates());
    }

    public function testPluginUpdaterNoUpdateContinues(): void
    {
        global $wp_remote_get_queue, $wp_remote_get_calls, $wp_delete_file_calls, $plugins_list;
        $plugins_list = [
            'a/a.php' => ['Version'=>'1.0.0'],
            'b/b.php' => ['Version'=>'1.0.0']
        ];
        $wp_remote_get_queue = [
            ['response'=>['code'=>204]],
            ['response'=>['code'=>204]]
        ];
        if (!function_exists('vontmnt_plugin_updater_run_updates')) {
            require __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        }
        vontmnt_plugin_updater_run_updates();
        $this->assertSame(2, $wp_remote_get_calls);
        $this->assertEmpty($wp_delete_file_calls);
    }

    public function testPluginUpdaterStopsOnHttpError(): void
    {
        global $wp_remote_get_queue, $wp_remote_get_calls, $plugins_list;
        $plugins_list = [
            'a/a.php' => ['Version'=>'1.0.0'],
            'b/b.php' => ['Version'=>'1.0.0']
        ];
        $wp_remote_get_queue = [
            ['response'=>['code'=>400]],
            ['response'=>['code'=>200],'body'=>'zip']
        ];
        if (!function_exists('vontmnt_plugin_updater_run_updates')) {
            require __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        }
        vontmnt_plugin_updater_run_updates();
        $this->assertSame(1, $wp_remote_get_calls);
    }

    public function testThemeUpdaterStopsOnHttpError(): void
    {
        global $wp_remote_get_queue, $wp_remote_get_calls, $themes_list;
        $themes_list = [
            new class { public function get_stylesheet(){ return 'a'; } public function get($f){ return '1.0.0'; } },
            new class { public function get_stylesheet(){ return 'b'; } public function get($f){ return '1.0.0'; } }
        ];
        $wp_remote_get_queue = [
            ['response'=>['code'=>403]],
            ['response'=>['code'=>200],'body'=>'zip']
        ];
        if (!function_exists('vontmnt_theme_updater_run_updates')) {
            require __DIR__ . '/../mu-plugin/v-sys-theme-updater.php';
        }
        vontmnt_theme_updater_run_updates();
        $this->assertSame(1, $wp_remote_get_calls);
    }

    public function testThemeUpdaterHandlesWpError(): void
    {
        global $wp_remote_get_queue;
        $wp_remote_get_queue = [new \WP_Error('err', 'fail')];
        if (!function_exists('vontmnt_theme_updater_run_updates')) {
            require __DIR__ . '/../mu-plugin/v-sys-theme-updater.php';
        }
        $this->assertNull(vontmnt_theme_updater_run_updates());
    }
}
}
