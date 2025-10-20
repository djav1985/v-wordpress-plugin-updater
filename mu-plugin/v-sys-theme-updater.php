<?php
// phpcs:ignoreFile
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: v-sys-theme-updater.php
 * Description: WordPress Update API
 *
 * Plugin Name: WP Theme Updater
 * Plugin URI: https://vontainment.com
 * Description: This plugin updates your WordPress themes.
 * Version: 1.2.0
 * Author: Vontainment
 * Author URI: https://vontainment.com
 * @package VontainmentThemeUpdater
*/

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Retrieve the API key, requesting from the server when needed.
 * Clients must use the stored API key option.
 */
if ( ! function_exists( 'vontmnt_get_api_key' ) ) {
function vontmnt_get_api_key(): string {
	$key = get_option( 'vontmnt_api_key' );
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
		$result = $zip->open( $file_path );
		if ( true !== $result ) {
			return false;
		}
		$zip->close();
	}
	
	return true;
}
}

/**
 * Redact the API key from a URL for logging purposes.
 *
 * @param string $url The URL that may contain an API key.
 * @return string The URL with the key parameter redacted.
 */
if ( ! function_exists( 'vontmnt_redact_key' ) ) {
function vontmnt_redact_key( string $url ): string {
	return preg_replace( '/([?&]key=)[^&]*/', '$1[REDACTED]', $url );
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
		vontmnt_redact_key( $url ),
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
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Schedule daily theme update checks. */
function vontmnt_theme_updater_schedule_updates(): void {
	if ( ! wp_next_scheduled( 'vontmnt_theme_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_theme_updater_check_updates' );
	}
}

add_action( 'admin_init', 'vontmnt_theme_updater_schedule_updates' );

add_action( 'vontmnt_theme_updater_check_updates', 'vontmnt_theme_updater_run_updates' );
add_action( 'vontmnt_theme_update_single', 'vontmnt_theme_update_single', 10, 2 );

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Schedule theme update checks for all installed themes. */
function vontmnt_theme_updater_run_updates(): void {
	// Atomic locking using add_option pattern
	$lock_key = 'vontmnt_theme_updates_scheduling';
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		// Lock exists, another scheduling run is in progress
		return;
	}
	
	if ( ! function_exists( 'wp_get_themes' ) ) {
		require_once ABSPATH . 'wp-includes/theme.php';
	}
	$themes = wp_get_themes();
	
	$when = time() + 5; // tiny jitter to reduce "same-second" collisions
	
	foreach ( $themes as $theme ) {
		$theme_slug        = $theme->get_stylesheet();
		$installed_version = $theme->get( 'Version' );
		// Schedule individual theme update check
		wp_schedule_single_event( $when, 'vontmnt_theme_update_single', array( $theme_slug, $installed_version ) );
		$when += rand( 0, 2 ); // Add small jitter per theme to further reduce collisions
	}
	
	// Release the lock
	delete_option( $lock_key );
}

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Update a single theme. */
function vontmnt_theme_update_single( string $theme_slug, string $installed_version ): void {
	// Atomic locking using add_option pattern
	$lock_key = 'vontmnt_updating_theme_' . md5( $theme_slug );
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		// Lock exists, another update is in progress
		return;
	}
	
	$api_url = add_query_arg(
		array(
			'type'    => 'theme',
			'domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
			'slug'    => $theme_slug,
			'version' => $installed_version,
			'key'     => vontmnt_get_api_key(),
		),
		VONTMNT_API_URL
	);

	$response = wp_remote_get( $api_url );
	if ( is_wp_error( $response ) ) {
		vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, 0, 0, 'failed', 'HTTP error: ' . $response->get_error_message() );
		delete_option( $lock_key );
		return;
	}
	
	$http_code     = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	// 401 responses (key rotation) are no longer supported; treat as failure

	if ( $http_code === 200 && ! empty( $response_body ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		
		// Initialize WP_Filesystem before any file operations
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		
		$upload_dir     = wp_upload_dir();
		$theme_zip_file = $upload_dir['path'] . '/' . $theme_slug . '.zip';
		
		// Stream large files to disk instead of loading into memory
		$temp_file = wp_tempnam( $theme_zip_file );
		$stream_response = wp_remote_get( $api_url, array( 'stream' => true, 'filename' => $temp_file ) );
		
		if ( is_wp_error( $stream_response ) ) {
			vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, $http_code, 0, 'failed', 'Failed to stream update package' );
			delete_option( $lock_key );
			return;
		}
		
		// Move temp file to final location
		if ( ! $wp_filesystem->move( $temp_file, $theme_zip_file ) ) {
			vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, $http_code, 0, 'failed', 'Failed to move update package' );
			wp_delete_file( $temp_file );
			delete_option( $lock_key );
			return;
		}
		
		$response_size = filesize( $theme_zip_file );

		// Validate ZIP package before installation
		if ( ! vontmnt_validate_zip_package( $theme_zip_file ) ) {
			vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, $http_code, $response_size, 'failed', 'Invalid ZIP package' );
			wp_delete_file( $theme_zip_file );
			delete_option( $lock_key );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$upgrader = new Theme_Upgrader();
		$callback = function ( $options ) use ( $theme_zip_file ) {
			$options['package']           = $theme_zip_file;
			$options['clear_destination'] = true;
			return $options;
		};
		add_filter( 'upgrader_package_options', $callback );
		$result = $upgrader->install( $theme_zip_file );
		remove_filter( 'upgrader_package_options', $callback );

		// Delete the theme zip file using wp_delete_file.
		wp_delete_file( $theme_zip_file );
		
		// Post-install housekeeping: refresh theme update data
		if ( function_exists( 'wp_clean_themes_cache' ) ) {
			wp_clean_themes_cache();
		}
		
		// Log success or failure
		if ( ! is_wp_error( $result ) && $result ) {
			vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, $http_code, $response_size, 'success', 'Theme updated successfully' );
		} else {
			$error_msg = is_wp_error( $result ) ? $result->get_error_message() : 'Installation failed';
			vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, $http_code, $response_size, 'failed', $error_msg );
		}
	} elseif ( 204 === $http_code ) {
		vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, $http_code, 0, 'skipped', 'No update available' );
	} else {
		vontmnt_log_update_context( 'theme', $theme_slug, $installed_version, $api_url, $http_code, 0, 'failed', 'Unexpected HTTP response' );
	}
	
	// Release the lock
	delete_option( $lock_key );
}
