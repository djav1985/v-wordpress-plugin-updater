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
 */
if ( ! function_exists( 'vontmnt_get_api_key' ) ) {
function vontmnt_get_api_key(): string {
        $key = get_option( 'vontmnt_api_key' );
        if ( ! $key || ( defined( 'VONTMNT_UPDATE_KEYREGEN' ) && VONTMNT_UPDATE_KEYREGEN ) ) {
                $base    = defined( 'VONTMENT_PLUGINS' ) ? VONTMENT_PLUGINS : ( defined( 'VONTMENT_THEMES' ) ? VONTMENT_THEMES : '' );
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

add_action( 'wp', 'vontmnt_theme_updater_schedule_updates' );

add_action( 'vontmnt_theme_updater_check_updates', 'vontmnt_theme_updater_run_updates' );

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Run theme updates for all installed themes. */
function vontmnt_theme_updater_run_updates(): void {
	if ( ! function_exists( 'wp_get_themes' ) ) {
		require_once ABSPATH . 'wp-includes/theme.php';
	}
	$themes = wp_get_themes();
	foreach ( $themes as $theme ) {
		$theme_slug        = $theme->get_stylesheet();
		$installed_version = $theme->get( 'Version' );
				$api_url   = add_query_arg(
					array(
						'type'    => 'theme',
						'domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
						'slug'    => $theme_slug,
						'version' => $installed_version,
                                               'key'     => vontmnt_get_api_key(),
					),
					VONTMENT_THEMES
				);

				$response = wp_remote_get( $api_url );
		if ( is_wp_error( $response ) ) {
				error_log( 'Theme updater error: ' . $response->get_error_message() );
				continue;
		}
				$http_code     = wp_remote_retrieve_response_code( $response );
				$response_body = wp_remote_retrieve_body( $response );

		if ( $http_code === 200 && ! empty( $response_body ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$upload_dir     = wp_upload_dir();
                        $theme_zip_file = $upload_dir['path'] . '/' . basename( $theme_slug ) . '.zip';
                        $bytes_written  = file_put_contents( $theme_zip_file, $response_body );
                        if ( false === $bytes_written ) {
                                error_log( 'Theme updater error: Failed to write update package for ' . $theme_slug );
                                continue;
                        }

			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			$upgrader = new Theme_Upgrader();
			$callback = function ( $options ) use ( $theme_zip_file ) {
				$options['package']           = $theme_zip_file;
				$options['clear_destination'] = true;
				return $options;
			};
			add_filter( 'upgrader_package_options', $callback );
			$upgrader->install( $theme_zip_file );
			remove_filter( 'upgrader_package_options', $callback );

			// Delete the theme zip file using wp_delete_file.
			wp_delete_file( $theme_zip_file );
		} elseif ( $http_code === 204 ) {
			// No updates, check next theme.
			continue;
		} else {
			// For 400, 403, or any other unexpected code, stop further processing.
			break;
		}
	}
}
