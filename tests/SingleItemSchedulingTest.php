<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

// Mock WordPress functions for testing
if (!function_exists(__NAMESPACE__ . '\\is_main_site')) {
    function is_main_site() {
        return true;
    }
}

if (!function_exists(__NAMESPACE__ . '\\wp_schedule_single_event')) {
    function wp_schedule_single_event($timestamp, $hook, $args = array()) {
        global $scheduled_events;
        if (!isset($scheduled_events)) {
            $scheduled_events = array();
        }
        $scheduled_events[] = array(
            'timestamp' => $timestamp,
            'hook' => $hook,
            'args' => $args
        );
        return true;
    }
}

if (!function_exists(__NAMESPACE__ . '\\get_plugins')) {
    function get_plugins() {
        return array(
            'plugin1/plugin1.php' => array('Version' => '1.0.0'),
            'plugin2/plugin2.php' => array('Version' => '2.0.0'),
        );
    }
}

if (!function_exists(__NAMESPACE__ . '\\wp_get_themes')) {
    function wp_get_themes() {
        $theme1 = new MockTheme('theme1', '1.0.0');
        $theme2 = new MockTheme('theme2', '2.0.0');
        return array($theme1, $theme2);
    }
}

class MockTheme {
    private $stylesheet;
    private $version;
    
    public function __construct($stylesheet, $version) {
        $this->stylesheet = $stylesheet;
        $this->version = $version;
    }
    
    public function get_stylesheet() {
        return $this->stylesheet;
    }
    
    public function get($key) {
        if ($key === 'Version') {
            return $this->version;
        }
        return null;
    }
}

class SingleItemSchedulingTest extends TestCase
{
    protected function setUp(): void
    {
        global $scheduled_events;
        $scheduled_events = array();
    }

    public function testPluginUpdaterSchedulesIndividualEvents(): void
    {
        global $scheduled_events;
        
        // Include and mock the functions we need
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater-mu.php';
        
        // Call the function that should schedule events
        \vontmnt_plugin_updater_run_updates();
        
        // Verify that events were scheduled
        $this->assertCount(2, $scheduled_events, 'Should schedule one event per plugin');
        
        // Check first event
        $this->assertEquals('vontmnt_plugin_update_single', $scheduled_events[0]['hook']);
        $this->assertEquals(array('plugin1/plugin1.php', '1.0.0'), $scheduled_events[0]['args']);
        
        // Check second event
        $this->assertEquals('vontmnt_plugin_update_single', $scheduled_events[1]['hook']);
        $this->assertEquals(array('plugin2/plugin2.php', '2.0.0'), $scheduled_events[1]['args']);
    }

    public function testThemeUpdaterSchedulesIndividualEvents(): void
    {
        global $scheduled_events;
        
        // Include and mock the functions we need  
        require_once __DIR__ . '/../mu-plugin/v-sys-theme-updater-mu.php';
        
        // Call the function that should schedule events
        \vontmnt_theme_updater_run_updates();
        
        // Verify that events were scheduled
        $this->assertCount(2, $scheduled_events, 'Should schedule one event per theme');
        
        // Check first event
        $this->assertEquals('vontmnt_theme_update_single', $scheduled_events[0]['hook']);
        $this->assertEquals(array('theme1', '1.0.0'), $scheduled_events[0]['args']);
        
        // Check second event
        $this->assertEquals('vontmnt_theme_update_single', $scheduled_events[1]['hook']);
        $this->assertEquals(array('theme2', '2.0.0'), $scheduled_events[1]['args']);
    }

    public function testSingleItemCallbacksExist(): void
    {
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater-mu.php';
        require_once __DIR__ . '/../mu-plugin/v-sys-theme-updater-mu.php';
        
        $this->assertTrue(function_exists('vontmnt_plugin_update_single'), 
            'Plugin single update function should exist');
        $this->assertTrue(function_exists('vontmnt_theme_update_single'), 
            'Theme single update function should exist');
    }
}