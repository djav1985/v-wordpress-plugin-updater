<?php
/**
 * Uninstall Functions
 *
 * Handles the uninstallation and cleanup of scheduled tasks.
 *
 * @package V_WP_Updater
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clears the scheduled plugin update event.
 *
 * Removes the scheduled event for plugin updates if it exists.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_clear_plugin_update_schedule(): void {
	if ( wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
		wp_clear_scheduled_hook( 'vontmnt_plugin_updater_check_updates' );
	}
}

/**
 * Clears the scheduled theme update event.
 *
 * Removes the scheduled event for theme updates if it exists.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_clear_theme_update_schedule(): void {
	if ( wp_next_scheduled( 'vontmnt_theme_updater_check_updates' ) ) {
		wp_clear_scheduled_hook( 'vontmnt_theme_updater_check_updates' );
	}
}
