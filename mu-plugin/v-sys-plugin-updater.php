<?php
// phpcs:ignoreFile
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
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

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Run plugin updates for all installed plugins. */
function vontmnt_plugin_updater_run_updates(): void {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugins = get_plugins();
	foreach ( $plugins as $plugin_path => $plugin ) {
		$plugin_slug       = dirname( $plugin_path );
		$installed_version = $plugin['Version'];
				$api_url   = add_query_arg(
					array(
						'type'    => 'plugin',
						'domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
						'slug'    => $plugin_slug,
						'version' => $installed_version,
						'key'     => VONTMENT_KEY,
					),
					VONTMENT_PLUGINS
				);

				// Use wp_remote_get instead of cURL.
				$response = wp_remote_get( $api_url );
		if ( is_wp_error( $response ) ) {
				error_log( 'Plugin updater error: ' . $response->get_error_message() );
				continue;
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
                                continue;
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
		} elseif ( $http_code === 204 ) {
			// No updates, check next plugin.
			continue;
		} else {
			// For 400, 403, or any other unexpected code, stop further processing.
			break;
		}
	}
}
