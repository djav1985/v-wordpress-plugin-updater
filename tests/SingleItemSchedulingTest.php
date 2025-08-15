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

if (!function_exists(__NAMESPACE__ . '\\wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) {
        global $scheduled_events;
        if (!isset($scheduled_events)) {
            return false;
        }
        foreach ($scheduled_events as $event) {
            if ($event['hook'] === $hook && $event['args'] === $args) {
                return $event['timestamp'];
            }
        }
        return false;
    }
}

if (!function_exists(__NAMESPACE__ . '\\get_transient')) {
    function get_transient($transient) {
        global $transients;
        return isset($transients[$transient]) ? $transients[$transient] : false;
    }
}

if (!function_exists(__NAMESPACE__ . '\\set_transient')) {
    function set_transient($transient, $value, $expiration) {
        global $transients;
        if (!isset($transients)) {
            $transients = array();
        }
        $transients[$transient] = $value;
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
        global $scheduled_events, $transients;
        $scheduled_events = array();
        $transients = array();
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
        
        // Verify timestamp has jitter (should be time() + 5)
        $this->assertGreaterThan(time() + 3, $scheduled_events[0]['timestamp']);
        $this->assertLessThan(time() + 7, $scheduled_events[0]['timestamp']);
    }

    public function testUniqueSchedulingPreventsDoubleBooking(): void
    {
        global $scheduled_events;
        
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater-mu.php';
        
        // First call should schedule events
        \vontmnt_plugin_updater_run_updates();
        $this->assertCount(2, $scheduled_events, 'First call should schedule 2 events');
        
        // Second call should be blocked by transient
        \vontmnt_plugin_updater_run_updates();
        $this->assertCount(2, $scheduled_events, 'Second call should not schedule additional events due to transient');
        
        // Clear transient and call again - should not add duplicates due to wp_next_scheduled check
        global $transients;
        $transients = array();
        
        \vontmnt_plugin_updater_run_updates();
        $this->assertCount(2, $scheduled_events, 'Third call should not schedule duplicates due to wp_next_scheduled check');
    }

    public function testTransientPreventsConcurrentScheduling(): void
    {
        global $transients;
        
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater-mu.php';
        
        // Simulate existing transient
        \set_transient('vontmnt_updates_scheduling', 1, 60);
        
        // Call should return early due to transient
        \vontmnt_plugin_updater_run_updates();
        
        global $scheduled_events;
        $this->assertCount(0, $scheduled_events, 'No events should be scheduled when transient is set');
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
        $this->assertTrue(function_exists('vontmnt_schedule_unique_single_event'), 
            'Unique scheduling helper function should exist');
    }

    public function testUniqueSchedulingHelperFunction(): void
    {
        global $scheduled_events;
        
        require_once __DIR__ . '/../mu-plugin/v-sys-plugin-updater-mu.php';
        
        $timestamp = time() + 10;
        $hook = 'test_hook';
        $args = array('test', 'args');
        
        // First call should schedule the event
        \vontmnt_schedule_unique_single_event($timestamp, $hook, $args);
        $this->assertCount(1, $scheduled_events, 'First call should schedule the event');
        
        // Second call with same hook+args should not schedule duplicate
        \vontmnt_schedule_unique_single_event($timestamp + 5, $hook, $args);
        $this->assertCount(1, $scheduled_events, 'Second call should not schedule duplicate event');
        
        // Call with different args should schedule new event
        \vontmnt_schedule_unique_single_event($timestamp, $hook, array('different', 'args'));
        $this->assertCount(2, $scheduled_events, 'Call with different args should schedule new event');
    }
}