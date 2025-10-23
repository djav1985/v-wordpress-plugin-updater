<?php
/**
 * Uninstall Functions
 *
 * Handles the uninstallation and cleanup of scheduled tasks.
 *
 * @package VWPU
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main uninstallation function that handles all plugin cleanup tasks.
 *
 * This function performs the complete uninstallation:
 * - Clears all scheduled cron jobs
 * - Deletes plugin options
 *
 * @since 2.0.0
 * @return void
 */
function vwpu_uninstall_cleanup(): void {
	// Clear all scheduled cron jobs.
	vwpu_clear_plugin_update_schedule();
	vwpu_clear_theme_update_schedule();

	// Delete plugin options.
	delete_option( 'vwpu-plup' );
	delete_option( 'vwpu-thup' );
}

/**
 * Clears the scheduled plugin update event.
 *
 * Removes the scheduled event for plugin updates if it exists.
 *
 * @since 1.0.0
 * @return void
 */
function vwpu_clear_plugin_update_schedule(): void {
	if ( wp_next_scheduled( 'vwpu_plugin_updater_check_updates' ) ) {
		wp_clear_scheduled_hook( 'vwpu_plugin_updater_check_updates' );
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
function vwpu_clear_theme_update_schedule(): void {
	if ( wp_next_scheduled( 'vwpu_theme_updater_check_updates' ) ) {
		wp_clear_scheduled_hook( 'vwpu_theme_updater_check_updates' );
	}
}
