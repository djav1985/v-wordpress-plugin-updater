<?php
/**
 * Install Functions
 *
 * Handles the installation and scheduling of updates for plugins and themes.
 *
 * @package V_WP_Dashboard
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use VWPDashboard\Helpers\Logger;
use VWPDashboard\Helpers\Options;

/**
 * Main installation function that handles all plugin activation tasks.
 *
 * This function performs the complete installation:
 * - Initializes default options
 * - Schedules all cron jobs
 * - Sets up autoload configuration
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_install(): void {
	// Initialize default options.
	Options::initialize_defaults();

	// Schedule all cron jobs (regardless of option values).
	vontmnt_schedule_plugin_updates();
	vontmnt_schedule_theme_updates();
	vontmnt_schedule_debug_log_deletion();
	vontmnt_schedule_remote_backups();

	// Ensure options are set with autoload disabled.
	$plup_val = get_option( 'vontmnt-plup', false );
	delete_option( 'vontmnt-plup' );
	add_option( 'vontmnt-plup', $plup_val, '', 'no' );

	$thup_val = get_option( 'vontmnt-thup', false );
	delete_option( 'vontmnt-thup' );
	add_option( 'vontmnt-thup', $thup_val, '', 'no' );

	Logger::info( 'Plugin activation completed successfully' );
}

/**
 * Schedules plugin updates if not already scheduled.
 *
 * Always schedules the cron job. The job handler will check option value when it runs.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_schedule_plugin_updates(): void {
	if ( ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
	}
}

/**
 * Schedules theme updates if not already scheduled.
 *
 * Always schedules the cron job. The job handler will check option value when it runs.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_schedule_theme_updates(): void {
	if ( ! wp_next_scheduled( 'vontmnt_theme_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vontmnt_theme_updater_check_updates' );
	}
}

/**
 * Schedules weekly deletion of debug logs if not already scheduled.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_schedule_debug_log_deletion(): void {
	if ( ! wp_next_scheduled( 'delete_debug_log_weekly_event' ) ) {
		wp_schedule_event( time(), 'weekly', 'delete_debug_log_weekly_event' );
	}
}

/**
 * Schedules remote backups if not already scheduled.
 *
 * Always schedules the cron job. The job handler will check option value when it runs.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_schedule_remote_backups(): void {
	if ( ! wp_next_scheduled( 'vontmnt_create_backup' ) ) {
		$timing = Options::get( 'remote_backups_timing', 'daily' );
		if ( empty( $timing ) ) {
			$timing = 'daily';
		}
		$schedules = wp_get_schedules();

		// Validate provided schedule; fallback to 'daily' if invalid.
		if ( ! isset( $schedules[ $timing ] ) ) {
			Logger::error(
				'Invalid remote_backups_timing, falling back to daily',
				array( 'timing' => $timing )
			);
			$timing = 'daily';
		}

		wp_schedule_event( time(), $timing, 'vontmnt_create_backup' );
	}
}

/**
 * Initialize default plugin options in the database.
 *
 * @deprecated 2.0.0 Use Options::initialize_defaults() instead.
 * @since 2.0.0
 * @return void
 */
function vontmnt_initialize_default_options(): void {
	Options::initialize_defaults();
}
