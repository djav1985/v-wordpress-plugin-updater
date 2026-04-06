<?php
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 2.0.0
 *
 * File: install.php
 * Description: V WordPress Plugin Updater
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

	// Preserve existing installs by migrating any legacy status keys.
	vwpu_migrate_legacy_status_options();

	// Schedule all cron jobs (regardless of option values).
	vwpu_schedule_plugin_updates();
	vwpu_schedule_theme_updates();

	// Ensure options are set with autoload disabled.
	$plugin_status = get_option( 'vwpu_plugin_update_status', false );
	delete_option( 'vwpu_plugin_update_status' );
	add_option( 'vwpu_plugin_update_status', $plugin_status, '', 'no' );

	$theme_status = get_option( 'vwpu_theme_update_status', false );
	delete_option( 'vwpu_theme_update_status' );
	add_option( 'vwpu_theme_update_status', $theme_status, '', 'no' );

	Logger::info( 'Plugin activation completed successfully' );
}

/**
 * Migrates legacy updater status option keys to canonical keys.
 *
 * @since 2.0.0
 * @return void
 */
function vwpu_migrate_legacy_status_options(): void {
	$migrations = array(
		'vontmnt-plup' => 'vwpu_plugin_update_status',
		'vwpu-plup'    => 'vwpu_plugin_update_status',
		'vontmnt-thup' => 'vwpu_theme_update_status',
		'vwpu-thup'    => 'vwpu_theme_update_status',
	);

	foreach ( $migrations as $legacy_key => $canonical_key ) {
		$legacy_value = get_option( $legacy_key, false );
		if ( false !== $legacy_value && false === get_option( $canonical_key, false ) ) {
			add_option( $canonical_key, $legacy_value, '', 'no' );
		}
		delete_option( $legacy_key );
	}
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
