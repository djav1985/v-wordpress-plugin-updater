<?php
/**
 * Plugin Name: V Sys Plugin Updater
 * Description: Provides helper functions used by the Vontainment updater tests.
 *
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 */

if (!function_exists('vontmnt_get_api_key')) {
    /**
     * Retrieve the stored Vontainment API key from WordPress options.
     *
     * @return string The API key, or an empty string when not set.
     */
    function vontmnt_get_api_key(): string
    {
        $stored = get_option('vontmnt_api_key');
        return is_string($stored) ? $stored : '';
    }
}
