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
function vontmnt_plugin_updater_schedule_updates(): void {
    if ( ! is_main_site() ) {
        return;
    }
    if ( ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
        wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
    }
}

add_action( 'vontmnt_plugin_updater_check_updates', 'vontmnt_plugin_updater_run_updates' );

/**
 * Run plugin updates for all installed plugins on the main site.
 */
function vontmnt_plugin_updater_run_updates(): void {
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
                               'type'    => 'plugin',
                               'domain'  => rawurlencode( wp_parse_url( site_url(), PHP_URL_HOST ) ),
                               'slug'    => rawurlencode( $plugin_slug ),
                               'version' => rawurlencode( $installed_version ),
                               'key'     => VONTMENT_KEY,
                       ),
                       VONTMENT_PLUGINS
               );

               // Use wp_remote_get instead of cURL.
               $response      = wp_remote_get( $api_url );
               $http_code     = wp_remote_retrieve_response_code( $response );
               $response_body = wp_remote_retrieve_body( $response );

               if ( 200 === $http_code && ! empty( $response_body ) ) {
                       require_once ABSPATH . 'wp-admin/includes/file.php';
                       $upload_dir      = wp_upload_dir();
                       $plugin_zip_file = $upload_dir['path'] . '/' . basename( $plugin_path ) . '.zip';
                       file_put_contents( $plugin_zip_file, $response_body );

                       global $wp_filesystem;
                       if ( empty( $wp_filesystem ) ) {
                               require_once ABSPATH . '/wp-admin/includes/file.php';
                               WP_Filesystem();
                       }

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
               } elseif ( 204 === $http_code ) {
                       continue;
               } else {
                       break;
               }
       }
}
