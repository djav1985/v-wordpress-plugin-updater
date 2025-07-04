<?php
/**
 * Plugin Name: WP Plugin Updater MU
 * Plugin URI: https://vontainment.com
 * Description: This plugin updates your WordPress plugins.
 * Version: 1.0.0
 * Author: Vontainment
 * Author URI: https://vontainment.com
 *
 * @package VontainmentPluginUpdaterMU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Schedule the update check to run every day.
add_action( 'wp', 'vontmnt_plugin_updater_schedule_updates' );


/**
 * Schedule daily plugin update checks for multisite.
 */
function vontmnt_plugin_updater_schedule_updates() {
	if ( ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
	}
}

add_action( 'vontmnt_plugin_updater_check_updates', 'vontmnt_plugin_updater_run_updates' );

/**
 * Run plugin updates for all installed plugins on the main site.
 */
function vontmnt_plugin_updater_run_updates() {
	// Check if it's the main site.
	if ( ! is_main_site() ) {
		return;
	}
	$plugins = get_plugins();
	foreach ( $plugins as $plugin_path => $plugin ) {
		$plugin_slug       = dirname( $plugin_path );
		$installed_version = $plugin['Version'];
		$api_url           = add_query_arg(
			array(
				'domain'  => rawurlencode( wp_parse_url( site_url(), PHP_URL_HOST ) ),
				'plugin'  => rawurlencode( $plugin_slug ),
				'version' => rawurlencode( $installed_version ),
				'key'     => VONTMENT_KEY,
			),
			VONTMENT_PLUGINS
		);

                // Use wp_remote_get instead of cURL.
                $response      = wp_remote_get( $api_url );
		$http_code     = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 204 === $http_code ) {
			continue;
		} elseif ( 401 === $http_code ) {
			continue;
		} elseif ( ! empty( $response_body ) ) {
			$response_data = json_decode( $response_body, true );

			if ( isset( $response_data['zip_url'] ) ) {
				$download_url = $response_data['zip_url'];

				require_once ABSPATH . 'wp-admin/includes/file.php';
				$upload_dir      = wp_upload_dir();
				$tmp_file        = download_url( $download_url );
				$plugin_zip_file = $upload_dir['path'] . '/' . basename( $download_url );

				// Move the downloaded file to the uploads directory using WP_Filesystem.
				global $wp_filesystem;
				if ( empty( $wp_filesystem ) ) {
					require_once ABSPATH . '/wp-admin/includes/file.php';
					WP_Filesystem();
				}
				$wp_filesystem->move( $tmp_file, $plugin_zip_file, true );

				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				$upgrader = new Plugin_Upgrader();
				add_filter(
					'upgrader_package_options',
					function ( $options ) use ( $plugin_zip_file ) {
						$options['package']           = $plugin_zip_file;
						$options['clear_destination'] = true;
						return $options;
					}
				);
				$upgrader->install( $plugin_zip_file );
				remove_all_filters( 'upgrader_package_options' );

				// Delete the plugin zip file using wp_delete_file.
				wp_delete_file( $plugin_zip_file );
			}
		}
	}
}
