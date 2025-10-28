<?php
/**
 * Plugin Name: V Sys Plugin Updater
 * Description: Provides helper functions used by the Vontainment updater tests.
 */

if (!function_exists('vontmnt_get_api_key')) {
    function vontmnt_get_api_key(): string
    {
        $stored = get_option('vontmnt_api_key');
        return is_string($stored) ? $stored : '';
    }
}
