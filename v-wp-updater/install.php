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
 * Schedules plugin updates if not already scheduled.
 *
 * Checks if the plugin update event is already scheduled. If not, schedules the event to run daily.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_plugin_updater_schedule_updates(): void {
	if ( vontmnt_option_is_true( 'update_plugins' ) && ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
	}
}

/**
 * Schedules theme updates if not already scheduled.
 *
 * Checks if the theme update event is already scheduled. If not, schedules the event to run daily.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_theme_updater_schedule_updates(): void {
	if ( vontmnt_option_is_true( 'update_themes' ) && ! wp_next_scheduled( 'vontmnt_theme_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_theme_updater_check_updates' );
	}
}

/**
 * Initialize default plugin options in the database.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_add_custom_constants_to_wp_config(): void {
	$defaults = array(
		'vontmnt_update_plugins'          => 'false',
		'vontmnt_update_themes'           => 'false',
		'vontmnt_update_key'              => '',
		'vontmnt_update_plugin_url'       => 'https://wp-updates.servicesbyv.com/plugins/api.php',
		'vontmnt_update_theme_url'        => 'https://wp-updates.servicesbyv.com/themes/api.php',
	);

	foreach ( $defaults as $option_name => $default_value ) {
		// Only add if option doesn't exist yet.
		if ( false === get_option( $option_name, false ) ) {
			add_option( $option_name, $default_value, '', 'no' );
		}
	}
}
