<?php
// phpcs:ignoreFile
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: v-sys-plugin-updater.php
 * Description: WordPress Update API
 *
 * Plugin Name: WP Plugin Updater
 * Plugin URI: https://vontainment.com
 * Description: This plugin updates your WordPress plugins.
 * Version: 1.2.0
 * Author: Vontainment
 * Author URI: https://vontainment.com
 * @package VontainmentPluginUpdater
*/

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Retrieve the API key, requesting from the server when needed.
 */
if ( ! function_exists( 'vontmnt_get_api_key' ) ) {
function vontmnt_get_api_key(): string {
        $key = get_option( 'vontmnt_api_key' );
        if ( ! $key || ( defined( 'VONTMNT_UPDATE_KEYREGEN' ) && VONTMNT_UPDATE_KEYREGEN ) ) {
                $base    = defined( 'VONTMNT_API_URL' ) ? VONTMNT_API_URL : '';
                $api_url = add_query_arg(
                        array(
                                'type'   => 'auth',
                                'domain' => wp_parse_url( site_url(), PHP_URL_HOST ),
                        ),
                        rtrim( $base, '/' ) . '/key'
                );
                $response = wp_remote_get( $api_url );
                if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
                        $key = wp_remote_retrieve_body( $response );
                        update_option( 'vontmnt_api_key', $key );
                        $wp_config = ABSPATH . 'wp-config.php';
                        if ( file_exists( $wp_config ) && is_writable( $wp_config ) ) {
                                $config = file_get_contents( $wp_config );
                                if ( false !== $config ) {
                                        $config = preg_replace( "/define\(\s*'VONTMNT_UPDATE_KEYREGEN'\s*,\s*true\s*\);/i", "define('VONTMNT_UPDATE_KEYREGEN', false);", $config );
                                        file_put_contents( $wp_config, $config );
                                }
                        }
                }
        }
        return is_string( $key ) ? $key : '';
}
}

/**
 * Schedule a single event only if an identical one (hook+args) isn't already queued.
 *
 * @param int    $timestamp The timestamp when the event should run.
 * @param string $hook      The action hook name.
 * @param array  $args      Arguments to pass to the hook.
 */
function vontmnt_schedule_unique_single_event( int $timestamp, string $hook, array $args ): void {
	if ( ! wp_next_scheduled( $hook, $args ) ) {
		wp_schedule_single_event( $timestamp, $hook, $args );
	}
}

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Schedule daily plugin update checks. */
function vontmnt_plugin_updater_schedule_updates(): void {
	if ( ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
	}
}

add_action( 'wp', 'vontmnt_plugin_updater_schedule_updates' );

add_action( 'vontmnt_plugin_updater_check_updates', 'vontmnt_plugin_updater_run_updates' );
add_action( 'vontmnt_plugin_update_single', 'vontmnt_plugin_update_single', 10, 2 );

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Schedule plugin update checks for all installed plugins. */
function vontmnt_plugin_updater_run_updates(): void {
	// Optional hardening: prevent two overlapping daily runs from piling up work
	if ( get_transient( 'vontmnt_updates_scheduling' ) ) {
		return;
	}
	set_transient( 'vontmnt_updates_scheduling', 1, 60 );

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugins = get_plugins();

	$when = time() + 5; // tiny jitter to reduce "same-second" collisions

	foreach ( $plugins as $plugin_path => $plugin ) {
		$args = array( $plugin_path, $plugin['Version'] );
		vontmnt_schedule_unique_single_event( $when, 'vontmnt_plugin_update_single', $args );
	}
}

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Update a single plugin. */
function vontmnt_plugin_update_single( $plugin_path, $installed_version ): void {
	$plugin_slug = dirname( $plugin_path );
	$api_url = add_query_arg(
		array(
			'type'    => 'plugin',
			'domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
			'slug'    => $plugin_slug,
			'version' => $installed_version,
			'key'     => vontmnt_get_api_key(),
		),
		VONTMNT_API_URL
	);

	// Use wp_remote_get instead of cURL.
	$response = wp_remote_get( $api_url );
	if ( is_wp_error( $response ) ) {
		error_log( 'Plugin updater error: ' . $response->get_error_message() );
		return;
	}
	$http_code     = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	if ( $http_code === 200 && ! empty( $response_body ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload_dir      = wp_upload_dir();
		$plugin_zip_file = $upload_dir['path'] . '/' . basename( $plugin_path ) . '.zip';
		$bytes_written   = file_put_contents( $plugin_zip_file, $response_body );
		if ( false === $bytes_written ) {
			error_log( 'Plugin updater error: Failed to write update package for ' . $plugin_slug );
			return;
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$upgrader = new Plugin_Upgrader();
		$callback = function ( $options ) use ( $plugin_zip_file ) {
			$options['package']           = $plugin_zip_file;
			$options['clear_destination'] = true;
			return $options;
		};
		add_filter( 'upgrader_package_options', $callback );
		$upgrader->install( $plugin_zip_file );
		remove_filter( 'upgrader_package_options', $callback );

		// Delete the plugin zip file using wp_delete_file.
		wp_delete_file( $plugin_zip_file );
	}
}
