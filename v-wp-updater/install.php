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
function v_updater_install(): void {
	// Schedule plugin update cron if not already scheduled.
	if ( ! wp_next_scheduled( 'v_updater_plugin_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'v_updater_plugin_check_updates' );
	}

	// Schedule theme update cron if not already scheduled.
	if ( ! wp_next_scheduled( 'v_updater_theme_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'v_updater_theme_check_updates' );
	}

	// Initialize default plugin options.
	$defaults = array(
		'v_updater_update_plugins'    => 'false',
		'v_updater_update_themes'     => 'false',
		'v_updater_update_key'        => '',
		'v_updater_update_plugin_url' => 'https://wp-updates.servicesbyv.com/plugins/api.php',
		'v_updater_update_theme_url'  => 'https://wp-updates.servicesbyv.com/themes/api.php',
	);

	foreach ( $defaults as $option_name => $default_value ) {
		// Only add if option doesn't exist yet.
		if ( false === get_option( $option_name, false ) ) {
			add_option( $option_name, $default_value, '', 'no' );
		}
	}
}
