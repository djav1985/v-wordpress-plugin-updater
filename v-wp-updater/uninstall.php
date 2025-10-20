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
 * Performs all uninstallation tasks.
 *
 * Clears all scheduled cron jobs.
 *
 * @since 2.0.0
 * @return void
 */
function v_updater_uninstall(): void {
	// Clear plugin update schedule.
	if ( wp_next_scheduled( 'v_updater_plugin_check_updates' ) ) {
		wp_clear_scheduled_hook( 'v_updater_plugin_check_updates' );
	}

	// Clear theme update schedule.
	if ( wp_next_scheduled( 'v_updater_theme_check_updates' ) ) {
		wp_clear_scheduled_hook( 'v_updater_theme_check_updates' );
	}
}
