<?php
/**
 * Plugin Name: V WordPress Plugin Updater
 * Plugin URI:  https://github.com/djav1985/v-wordpress-plugin-updater
 * Description: Automated plugin and theme updater with REST API support for remote management.
 * Version:     2.0.0
 * Author:      Vontainment
 * Author URI:  https://vontainment.com
 * License:     MIT
 * Text Domain: v-wp-updater
 *
 * @package VWPU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

use VWPU\Services\PluginUpdater;
use VWPU\Services\ThemeUpdater;
use VWPU\Api\PluginApi;
use VWPU\Api\ThemeApi;
use VWPU\Api\DebugLogApi;
use VWPU\Helpers\Logger;
use VWPU\Helpers\Options;

// Determine execution context flags up front so conditional bootstrapping can
// avoid loading admin-specific functionality on frontend requests.
$is_admin_context = is_admin();

/**
 * Runs on plugin activation.
 *
 * Calls the main installation function to handle all setup tasks.
 *
 * @since 1.0.0
 * @return void
 */
function vwpu_activate(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	try {
		include_once plugin_dir_path( __FILE__ ) . 'install.php';
		vwpu_install();
	} catch ( Exception $e ) {
		Logger::error( 'Activation error', array( 'exception' => $e->getMessage() ) );
	}
}
register_activation_hook( __FILE__, 'vwpu_activate' );

/**
 * Runs on plugin deactivation.
 *
 * Clears all scheduled events without deleting stored options so settings persist.
 *
 * @since 2.0.0
 * @return void
 */
function vwpu_deactivate(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
	}

	try {
			include_once plugin_dir_path( __FILE__ ) . 'uninstall.php';
			vwpu_clear_plugin_update_schedule();
			vwpu_clear_theme_update_schedule();
	} catch ( Exception $e ) {
			Logger::error( 'Deactivation error', array( 'exception' => $e->getMessage() ) );
	}
}
register_deactivation_hook( __FILE__, 'vwpu_deactivate' );

/**
 * Runs on plugin uninstall.
 *
 * Calls the main uninstallation function to handle all cleanup tasks.
 *
 * @since 1.0.0
 * @return void
 */
function vwpu_uninstall(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
	}

	try {
			include_once plugin_dir_path( __FILE__ ) . 'uninstall.php';
			vwpu_uninstall_cleanup();
	} catch ( Exception $e ) {
			Logger::error( 'Uninstall error', array( 'exception' => $e->getMessage() ) );
	}
}
register_uninstall_hook( __FILE__, 'vwpu_uninstall' );

/**
 * Sets up dashboard widgets and styles.
 *
 * Adds custom dashboard widgets and styles for the WordPress admin dashboard.
 *
 * @since 1.0.0
 * @return void
 */
function vwpu_dashboard_setup(): void {
	$widgets_dir = plugin_dir_path( __FILE__ ) . 'widgets/';

	if ( file_exists( $widgets_dir . 'settings.php' ) ) {
		require_once $widgets_dir . 'settings.php';
		wp_add_dashboard_widget(
			'vwpu_settings_widget',
			__( 'V WordPress Updater Settings', 'v-wp-updater' ),
			'vwpu_widget_settings_display'
		);
	}
}
if ( $is_admin_context ) {
		add_action( 'wp_dashboard_setup', 'vwpu_dashboard_setup' );
}

/**
 * Run theme updater if enabled.
 */
function vwpu_run_theme_updater() {
	if ( ! Options::is_true( 'update_themes' ) ) {
		return;
	}
		$theme_updater = new ThemeUpdater();
	$theme_updater->run_updates();
}
add_action( 'vwpu_theme_updater_check_updates', 'vwpu_run_theme_updater' );

/**
 * Run plugin updater if enabled.
 */
function vwpu_run_plugin_updater() {
	if ( ! Options::is_true( 'update_plugins' ) ) {
		return;
	}
		$plugin_updater = new PluginUpdater();
	$plugin_updater->run_updates();
}
add_action( 'vwpu_plugin_updater_check_updates', 'vwpu_run_plugin_updater' );

/**
 * Initialize Plugin API if update_key is set.
 */
function vwpu_initialize_plugin_api(): void {
	if ( ! ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) ) {
			return;
	}

		$update_key = Options::get( 'update_key' );
	if ( empty( $update_key ) ) {
			return;
	}

		PluginApi::get_instance();
		ThemeApi::get_instance();
		DebugLogApi::get_instance();
}
add_action( 'init', 'vwpu_initialize_plugin_api' );
