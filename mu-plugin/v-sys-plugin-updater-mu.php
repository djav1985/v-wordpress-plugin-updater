<?php
// phpcs:ignoreFile
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: v-sys-plugin-updater-mu.php
 * Description: WordPress Update API
 *
 * Plugin Name: WP Plugin Updater MU
 * Plugin URI: https://vontainment.com
 * Description: This plugin updates your WordPress plugins.
 * Version: 1.0.0
 * Author: Vontainment
 * Author URI: https://vontainment.com
 * @package VontainmentPluginUpdaterMU
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
 * Validate ZIP package by checking header and optionally testing extraction.
 *
 * @param string $file_path Path to ZIP file.
 * @return bool True if valid ZIP package.
 */
if ( ! function_exists( 'vontmnt_validate_zip_package' ) ) {
function vontmnt_validate_zip_package( string $file_path ): bool {
	if ( ! file_exists( $file_path ) ) {
		return false;
	}
	
	// Check ZIP file header (magic bytes: PK\x03\x04)
	$handle = fopen( $file_path, 'rb' );
	if ( false === $handle ) {
		return false;
	}
	
	$header = fread( $handle, 4 );
	fclose( $handle );
	
	if ( 4 !== strlen( $header ) || "\x50\x4b\x03\x04" !== $header ) {
		return false;
	}
	
	// Test ZIP extraction using ZipArchive if available
	if ( class_exists( 'ZipArchive' ) ) {
		$zip = new ZipArchive();
		$result = $zip->open( $file_path, ZipArchive::CHECKCONS );
		if ( true !== $result ) {
			return false;
		}
		$zip->close();
	}
	
	return true;
}
}

/**
 * Log update context with detailed information.
 *
 * @param string $type Update type (plugin/theme).
 * @param string $slug Item slug.
 * @param string $version Item version.
 * @param string $url API URL called.
 * @param int    $response_code HTTP response code.
 * @param int    $response_size Response body size in bytes.
 * @param string $status Update status (success/failed/skipped).
 * @param string $message Optional message.
 */
if ( ! function_exists( 'vontmnt_log_update_context' ) ) {
function vontmnt_log_update_context( string $type, string $slug, string $version, string $url, int $response_code, int $response_size, string $status, string $message = '' ): void {
	$context = sprintf(
		'[%s] %s:%s | URL: %s | HTTP: %d | Size: %d bytes | Status: %s',
		strtoupper( $type ),
		$slug,
		$version,
		$url,
		$response_code,
		$response_size,
		$status
	);
	
	if ( ! empty( $message ) ) {
		$context .= ' | ' . $message;
	}
	
	error_log( $context );
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

// Schedule the update check to run every day.
add_action( 'wp', 'vontmnt_plugin_updater_schedule_updates' );


/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Schedule daily plugin update checks for multisite. */
function vontmnt_plugin_updater_schedule_updates(): void {
	if ( ! is_main_site() ) {
		return;
	}
	if ( ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
	}
}

add_action( 'vontmnt_plugin_updater_check_updates', 'vontmnt_plugin_updater_run_updates' );
add_action( 'vontmnt_plugin_update_single', 'vontmnt_plugin_update_single', 10, 2 );

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Schedule plugin update checks for all installed plugins on the main site. */
function vontmnt_plugin_updater_run_updates(): void {
	// Check if it's the main site.
	if ( ! is_main_site() ) {
		return;
	}

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
	// Check if it's the main site.
	if ( ! is_main_site() ) {
		return;
	}
	
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
		vontmnt_log_update_context( 'plugin', $plugin_slug, $installed_version, $api_url, 0, 0, 'failed', 'HTTP error: ' . $response->get_error_message() );
		return;
	}
	
	$http_code     = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$response_size = strlen( $response_body );

	if ( 200 === $http_code && ! empty( $response_body ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		
		// Initialize WP_Filesystem before any file operations
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		
		$upload_dir      = wp_upload_dir();
		$plugin_zip_file = $upload_dir['path'] . '/' . basename( $plugin_path ) . '.zip';
		
		// Use WP_Filesystem instead of file_put_contents
		$bytes_written = $wp_filesystem->put_contents( $plugin_zip_file, $response_body );
		if ( false === $bytes_written ) {
			vontmnt_log_update_context( 'plugin', $plugin_slug, $installed_version, $api_url, $http_code, $response_size, 'failed', 'Failed to write update package' );
			return;
		}

		// Validate ZIP package before installation
		if ( ! vontmnt_validate_zip_package( $plugin_zip_file ) ) {
			vontmnt_log_update_context( 'plugin', $plugin_slug, $installed_version, $api_url, $http_code, $response_size, 'failed', 'Invalid ZIP package' );
			wp_delete_file( $plugin_zip_file );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$upgrader = new Plugin_Upgrader();
		$callback = function ( $options ) use ( $plugin_zip_file ) {
			$options['package']           = $plugin_zip_file;
			$options['clear_destination'] = true;
			return $options;
		};
		add_filter( 'upgrader_package_options', $callback );
		$result = $upgrader->install( $plugin_zip_file );
		remove_filter( 'upgrader_package_options', $callback );

		// Delete the plugin zip file using wp_delete_file.
		wp_delete_file( $plugin_zip_file );
		
		// Log success or failure
		if ( ! is_wp_error( $result ) && $result ) {
			vontmnt_log_update_context( 'plugin', $plugin_slug, $installed_version, $api_url, $http_code, $response_size, 'success', 'Plugin updated successfully' );
		} else {
			$error_msg = is_wp_error( $result ) ? $result->get_error_message() : 'Installation failed';
			vontmnt_log_update_context( 'plugin', $plugin_slug, $installed_version, $api_url, $http_code, $response_size, 'failed', $error_msg );
		}
	} elseif ( 204 === $http_code ) {
		vontmnt_log_update_context( 'plugin', $plugin_slug, $installed_version, $api_url, $http_code, $response_size, 'skipped', 'No update available' );
	} else {
		vontmnt_log_update_context( 'plugin', $plugin_slug, $installed_version, $api_url, $http_code, $response_size, 'failed', 'Unexpected HTTP response' );
	}
}
