<?php
/**
 * Install Functions
 *
 * Handles the installation and scheduling of updates for plugins and themes.
 *
 * @package VWPU
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use VWPU\Helpers\Logger;
use VWPU\Helpers\Options;

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
function vwpu_install(): void {
	// Initialize default options.
	Options::initialize_defaults();

	// Schedule all cron jobs (regardless of option values).
	vwpu_schedule_plugin_updates();
	vwpu_schedule_theme_updates();

	// Ensure options are set with autoload disabled.
	$plup_val = get_option( 'vwpu-plup', false );
	delete_option( 'vwpu-plup' );
	add_option( 'vwpu-plup', $plup_val, '', 'no' );

	$thup_val = get_option( 'vwpu-thup', false );
	delete_option( 'vwpu-thup' );
	add_option( 'vwpu-thup', $thup_val, '', 'no' );

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
function vwpu_schedule_plugin_updates(): void {
	if ( ! wp_next_scheduled( 'vwpu_plugin_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vwpu_plugin_updater_check_updates' );
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
function vwpu_schedule_theme_updates(): void {
	if ( ! wp_next_scheduled( 'vwpu_theme_updater_check_updates' ) ) {
		wp_schedule_event( time(), 'daily', 'vwpu_theme_updater_check_updates' );
	}
}

/**
 * Initialize default plugin options in the database.
 *
 * @deprecated 2.0.0 Use Options::initialize_defaults() instead.
 * @since 2.0.0
 * @return void
 */
function vwpu_initialize_default_options(): void {
	Options::initialize_defaults();
}
