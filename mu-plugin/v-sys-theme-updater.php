<?php
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
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
						'key'     => VONTMENT_KEY,
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
			file_put_contents( $theme_zip_file, $response_body );

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
