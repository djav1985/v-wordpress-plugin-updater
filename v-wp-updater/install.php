<?php
/**
 * Install Functions
 *
 * Handles the installation and scheduling of updates for plugins and themes.
 *
 * @package V_WP_Updater
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs all installation tasks.
 *
 * Schedules cron jobs and initializes default options.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_install(): void {
	// Schedule plugin update cron if not already scheduled.
	if ( ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
	}

	// Schedule theme update cron if not already scheduled.
	if ( ! wp_next_scheduled( 'vontmnt_theme_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_theme_updater_check_updates' );
	}

	// Initialize default plugin options.
	$defaults = array(
		'vontmnt_update_plugins'    => 'false',
		'vontmnt_update_themes'     => 'false',
		'vontmnt_update_key'        => '',
		'vontmnt_update_plugin_url' => 'https://wp-updates.servicesbyv.com/plugins/api.php',
		'vontmnt_update_theme_url'  => 'https://wp-updates.servicesbyv.com/themes/api.php',
	);

	foreach ( $defaults as $option_name => $default_value ) {
		// Only add if option doesn't exist yet.
		if ( false === get_option( $option_name, false ) ) {
			add_option( $option_name, $default_value, '', 'no' );
		}
	}
}
