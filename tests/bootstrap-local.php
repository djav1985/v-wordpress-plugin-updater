<?php
// Lightweight bootstrap for local PHPUnit runs.
// Define minimal WordPress function stubs used by tests and provide
// an in-memory scheduler/transient storage so tests can assert on calls.

// Simple in-memory storage for transients and scheduled events.
global $__vontmnt_test_storage;
$__vontmnt_test_storage = [
    'transients' => [],
    'scheduled'  => [],
    'options'    => [],
];

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        global $__vontmnt_test_storage;
        $__vontmnt_test_storage['transients'][$transient] = $value;
        // Mirror into legacy test global for compatibility
        if (isset($GLOBALS['transients']) && is_array($GLOBALS['transients'])) {
            $GLOBALS['transients'][$transient] = $value;
        }
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        global $__vontmnt_test_storage;
        return $__vontmnt_test_storage['transients'][$transient] ?? false;
    }
}

if (!function_exists('wp_schedule_single_event')) {
    function wp_schedule_single_event($timestamp, $hook, $args = array()) {
        global $__vontmnt_test_storage;
        $__vontmnt_test_storage['scheduled'][] = compact('timestamp','hook','args');
        // Mirror into legacy test global for compatibility
        if (isset($GLOBALS['scheduled_events']) && is_array($GLOBALS['scheduled_events'])) {
            $GLOBALS['scheduled_events'][] = array('timestamp' => $timestamp, 'hook' => $hook, 'args' => $args);
        }
        return true;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) {
        global $__vontmnt_test_storage;
        foreach ($__vontmnt_test_storage['scheduled'] as $ev) {
            if ($ev['hook'] === $hook && ($args === array() || $ev['args'] === $args)) {
                return $ev['timestamp'];
            }
        }
        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = array()) {
        global $__vontmnt_test_storage;
        $__vontmnt_test_storage['scheduled'][] = compact('timestamp','hook','args','recurrence');
        if (isset($GLOBALS['scheduled_events']) && is_array($GLOBALS['scheduled_events'])) {
            $GLOBALS['scheduled_events'][] = array('timestamp' => $timestamp, 'hook' => $hook, 'args' => $args, 'recurrence' => $recurrence);
        }
        return true;
    }
}

if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook, $args = array()) {
        global $__vontmnt_test_storage;
        foreach ($__vontmnt_test_storage['scheduled'] as $i => $ev) {
            if ($ev['hook'] === $hook && $ev['timestamp'] === $timestamp && ($args === array() || $ev['args'] === $args)) {
                unset($__vontmnt_test_storage['scheduled'][$i]);
                // Mirror removal in legacy global
                if (isset($GLOBALS['scheduled_events']) && is_array($GLOBALS['scheduled_events'])) {
                    foreach ($GLOBALS['scheduled_events'] as $j => $gev) {
                        if ($gev['hook'] === $hook && $gev['timestamp'] === $timestamp) {
                            unset($GLOBALS['scheduled_events'][$j]);
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook, $args = array()) {
        global $__vontmnt_test_storage;
        foreach ($__vontmnt_test_storage['scheduled'] as $i => $ev) {
            if ($ev['hook'] === $hook && ($args === array() || $ev['args'] === $args)) {
                unset($__vontmnt_test_storage['scheduled'][$i]);
            }
        }
        // Return void in WP implementation.
    }
}

if (!function_exists('get_option')) {
    function get_option($name, $default = false) {
        global $__vontmnt_test_storage;
        // Allow tests that set a global $options array to override values.
        if (isset($GLOBALS['options']) && is_array($GLOBALS['options'])) {
            if (array_key_exists($name, $GLOBALS['options'])) {
                return $GLOBALS['options'][$name];
            }
        }
        return $__vontmnt_test_storage['options'][$name] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($name, $value, $autoload = null) {
        global $__vontmnt_test_storage;
        // Mirror into global $options if tests use it.
        if (isset($GLOBALS['options']) && is_array($GLOBALS['options'])) {
            $GLOBALS['options'][$name] = $value;
        }
        $__vontmnt_test_storage['options'][$name] = $value;
        return true;
    }
}

if (!function_exists('add_option')) {
    function add_option($name, $value = '', $deprecated = '', $autoload = 'yes') {
        global $__vontmnt_test_storage;
        if (isset($__vontmnt_test_storage['options'][$name])) {
            return false;
        }
        $__vontmnt_test_storage['options'][$name] = $value;
        if (isset($GLOBALS['options']) && is_array($GLOBALS['options'])) {
            $GLOBALS['options'][$name] = $value;
        }
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($name) {
        global $__vontmnt_test_storage;
        if (isset($__vontmnt_test_storage['options'][$name])) {
            unset($__vontmnt_test_storage['options'][$name]);
        }
        if (isset($GLOBALS['options']) && is_array($GLOBALS['options']) && array_key_exists($name, $GLOBALS['options'])) {
            unset($GLOBALS['options'][$name]);
        }
        return true;
    }
}

// Ensure test sqlite exists for DatabaseManager tests.
$testSqlite = __DIR__ . '/../update-api/storage/test.sqlite';
if (!file_exists($testSqlite)) {
    // Create empty SQLite file; DatabaseManager should create tables as needed in tests.
    @mkdir(dirname($testSqlite), 0777, true);
    file_put_contents($testSqlite, "");
}

// Define ABSPATH so mu-plugin guard clauses don't exit on include.
if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/');
}

// No-op hook registration functions commonly used in mu-plugins.
if (!function_exists('add_action')) { function add_action(...$args) { return true; } }
if (!function_exists('add_filter')) { function add_filter(...$args) { return true; } }
if (!function_exists('remove_filter')) { function remove_filter(...$args) { return true; } }

// Additional WordPress functions needed by mu-plugin tests
if (!function_exists('is_main_site')) { function is_main_site() { return true; } }
if (!function_exists('wp_parse_url')) { function wp_parse_url($url, $component = -1) { return parse_url($url, $component); } }
if (!function_exists('wp_upload_dir')) { function wp_upload_dir() { return ['basedir' => sys_get_temp_dir() . '/wp-content/uploads']; } }
if (!function_exists('wp_tempnam')) { function wp_tempnam($filename = '', $dir = '') { return tempnam($dir ?: sys_get_temp_dir(), $filename); } }
if (!function_exists('wp_mkdir_p')) { function wp_mkdir_p($target) { return @mkdir($target, 0777, true) || is_dir($target); } }
if (!function_exists('wp_delete_file')) { function wp_delete_file($file) { return @unlink($file); } }
if (!function_exists('wp_filesystem')) { function wp_filesystem() { return true; } }
if (!function_exists('wp_clean_plugins_cache')) { function wp_clean_plugins_cache() { return true; } }
if (!function_exists('wp_clean_themes_cache')) { function wp_clean_themes_cache() { return true; } }
if (!function_exists('wp_remote_get')) { 
    function wp_remote_get($url, $args = []) {
        global $__wp_remote_responses;
        if (isset($__wp_remote_responses[$url])) {
            return $__wp_remote_responses[$url];
        }
        return ['response' => ['code' => 200], 'body' => ''];
    }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return $response['response']['code'] ?? 200;
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}
if (!function_exists('get_plugins')) {
    function get_plugins() {
        global $__wp_plugins;
        return $__wp_plugins ?? [];
    }
}
if (!function_exists('wp_get_themes')) {
    function wp_get_themes() {
        global $__wp_themes;
        return $__wp_themes ?? [];
    }
}

// Allow vendor autoload to be loaded by phpunit.xml bootstrap (vendor/autoload.php)
// If phpunit.xml already bootstraps vendor/autoload.php, this file will be included after.
