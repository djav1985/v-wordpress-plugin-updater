<?php

namespace {
    // Mock WordPress functions needed for the test
    $key_refresh_test_options = [];
    $key_refresh_test_remote_calls = [];
    
    if (!function_exists('get_option')) { 
        function get_option($name) { 
            global $key_refresh_test_options; 
            return $key_refresh_test_options[$name] ?? false; 
        } 
    }
    
    if (!function_exists('update_option')) { 
        function update_option($name, $value, $autoload = null) { 
            global $key_refresh_test_options; 
            $key_refresh_test_options[$name] = $value; 
            return true; 
        } 
    }
    
    if (!function_exists('wp_parse_url')) { 
        function wp_parse_url($url, $component) { 
            return 'example.com'; 
        } 
    }
    
    if (!function_exists('site_url')) { 
        function site_url() { 
            return 'https://example.com'; 
        } 
    }
    
    if (!function_exists('add_query_arg')) { 
        function add_query_arg($args, $url) { 
            $query = http_build_query($args, '', '&', PHP_QUERY_RFC3986); 
            return rtrim($url, '/') . '/key?' . $query; 
        } 
    }
    
    if (!function_exists('wp_remote_get')) { 
        function wp_remote_get($url) { 
            global $key_refresh_test_remote_calls; 
            $key_refresh_test_remote_calls[] = $url;
            
            // Simulate different responses based on URL parameters
            if (strpos($url, 'old_key=old-key-123') !== false) {
                // This is a key refresh request with old key
                return ['body' => 'new-refreshed-key-456', 'response' => ['code' => 200]]; 
            } else {
                // Standard key request
                return ['body' => 'initial-key-789', 'response' => ['code' => 200]]; 
            }
        } 
    }
    
    if (!function_exists('wp_remote_retrieve_response_code')) { 
        function wp_remote_retrieve_response_code($response) { 
            return $response['response']['code']; 
        } 
    }
    
    if (!function_exists('wp_remote_retrieve_body')) { 
        function wp_remote_retrieve_body($response) { 
            return $response['body']; 
        } 
    }
    
    if (!function_exists('is_wp_error')) { 
        function is_wp_error($thing) { 
            return false; 
        } 
    }
    
    if (!defined('VONTMNT_API_URL')) {
        define('VONTMNT_API_URL', 'https://example.com/api');
    }
}

namespace Tests {

use PHPUnit\Framework\TestCase;

class KeyRefreshWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        global $key_refresh_test_options, $key_refresh_test_remote_calls;
        $key_refresh_test_options = [];
        $key_refresh_test_remote_calls = [];
    }

    public function testInitialKeyFetch(): void
    {
        global $key_refresh_test_remote_calls;
        
        // Clear any existing options
        global $key_refresh_test_options;
        $key_refresh_test_options = [];
        
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        
        // Test initial key fetch (no key stored)
        $key = \vontmnt_get_api_key();
        
        $this->assertSame('initial-key-789', $key);
        $this->assertCount(1, $key_refresh_test_remote_calls);
        $this->assertStringContainsString('type=auth', $key_refresh_test_remote_calls[0]);
        $this->assertStringContainsString('domain=example.com', $key_refresh_test_remote_calls[0]);
        $this->assertStringNotContainsString('old_key', $key_refresh_test_remote_calls[0]);
        
        // Verify key was stored
        $this->assertSame('initial-key-789', $key_refresh_test_options['vontmnt_api_key']);
    }

    public function testKeyRefreshWorkflow(): void
    {
        global $key_refresh_test_options, $key_refresh_test_remote_calls;
        
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        
        // Set up scenario where old key exists
        $key_refresh_test_options['vontmnt_api_key'] = 'old-key-123';
        
        // Test key refresh
        $new_key = \vontmnt_refresh_api_key();
        
        $this->assertSame('new-refreshed-key-456', $new_key);
        $this->assertCount(1, $key_refresh_test_remote_calls);
        $this->assertStringContainsString('type=auth', $key_refresh_test_remote_calls[0]);
        $this->assertStringContainsString('domain=example.com', $key_refresh_test_remote_calls[0]);
        $this->assertStringContainsString('old_key=old-key-123', $key_refresh_test_remote_calls[0]);
        
        // Verify key was updated in options
        $this->assertSame('new-refreshed-key-456', $key_refresh_test_options['vontmnt_api_key']);
    }

    public function testSubsequentKeyGetUsesStoredKey(): void
    {
        global $key_refresh_test_options, $key_refresh_test_remote_calls;
        
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        
        // Set up scenario where key already exists
        $key_refresh_test_options['vontmnt_api_key'] = 'existing-key';
        
        // Test that existing key is returned without remote call
        $key = \vontmnt_get_api_key();
        
        $this->assertSame('existing-key', $key);
        $this->assertCount(0, $key_refresh_test_remote_calls); // No remote calls should be made
    }
}

}