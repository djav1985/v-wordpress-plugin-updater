<?php
/**
 * Plugin Name: WP By Vontainment
 * Plugin URI:  https://vontainment.com
 * Description: Our custom enhancements, optimizations, updates, backups, security and support provided by Vontainment Premium Services.
 * Version:     2.0.0
 * Author:      Vontainment
 * Author URI:  https://vontainment.com
 * License:     MIT
 * Text Domain: v-wp-dashboard
 *
 * @package V_WP_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

use VWPDashboard\Services\CacheClearer;
use VWPDashboard\Services\PluginUpdater;
use VWPDashboard\Services\RemoteBackup;
use VWPDashboard\Services\ThemeUpdater;
use VWPDashboard\Services\Impersonation;
use VWPDashboard\Api\PluginApi;
use VWPDashboard\Api\ThemeApi;
use VWPDashboard\Api\DebugLogApi;
use VWPDashboard\Utilities\Ajax;
use VWPDashboard\Utilities\Cron;
use VWPDashboard\Helpers\Logger;
use VWPDashboard\Helpers\Options;
use VWPDashboard\Helpers\Security;
use VWPDashboard\Services\SupportBot;
use VWPDashboard\Utilities\WidgetRegistry;

// Determine execution context flags up front so conditional bootstrapping can
// avoid loading admin-specific functionality on frontend requests.
$is_admin_context = is_admin();
$doing_ajax       = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();

// Initialize utility classes only when an administrative or AJAX context is in play.
if ( $is_admin_context || $doing_ajax ) {
        Ajax::register_handlers();

        if ( $is_admin_context ) {
                SupportBot::init();
        }
}

Cron::register_schedules();

/**
 * Runs on plugin activation.
 *
 * Calls the main installation function to handle all setup tasks.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_dashboard_activate(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	try {
		include_once plugin_dir_path( __FILE__ ) . 'install.php';
		vontmnt_install();
	} catch ( Exception $e ) {
		Logger::error( 'Activation error', array( 'exception' => $e->getMessage() ) );
	}
}
register_activation_hook( __FILE__, 'vontmnt_dashboard_activate' );

/**
 * Runs on plugin deactivation.
 *
 * Clears all scheduled events without deleting stored options so settings persist.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_dashboard_deactivate(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
	}

	try {
			include_once plugin_dir_path( __FILE__ ) . 'uninstall.php';
			vontmnt_clear_plugin_update_schedule();
			vontmnt_clear_theme_update_schedule();
			vontmnt_clear_debug_log_deletion();
			vontmnt_clear_backup_creation_schedule();
	} catch ( Exception $e ) {
			Logger::error( 'Deactivation error', array( 'exception' => $e->getMessage() ) );
	}
}
register_deactivation_hook( __FILE__, 'vontmnt_dashboard_deactivate' );

/**
 * Runs on plugin uninstall.
 *
 * Calls the main uninstallation function to handle all cleanup tasks.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_dashboard_uninstall(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
	}

	try {
			include_once plugin_dir_path( __FILE__ ) . 'uninstall.php';
			vontmnt_uninstall();
	} catch ( Exception $e ) {
			Logger::error( 'Uninstall error', array( 'exception' => $e->getMessage() ) );
	}
}
register_uninstall_hook( __FILE__, 'vontmnt_dashboard_uninstall' );

/**
 * Sets up dashboard widgets and styles.
 *
 * Adds custom dashboard widgets and styles for the WordPress admin dashboard.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_dashboard_setup(): void {
                // Enqueue admin dashboard styles for V_WP_Dashboard widgets.
                $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && 'dashboard' === $screen->id ) {
                        wp_enqueue_style(
                                'v-wp-dashboard-admin',
                                plugin_dir_url( __FILE__ ) . 'assets/styles.css',
                                array(),
                                filemtime( plugin_dir_path( __FILE__ ) . 'assets/styles.css' )
                        );
        }

		// Remove default WordPress widgets.
		WidgetRegistry::remove_default_widgets();

		$widgets_dir = plugin_dir_path( __FILE__ ) . 'widgets/';

		// Ensure the settings widget is only available to privileged Vontainment administrators.
		$widget_overrides = array();
	if ( ! ( Security::is_vontainment_user() && Security::can_manage_options() ) ) {
			$widget_overrides['settings.php'] = false;
	}

		// Register all dashboard widgets automatically.
		WidgetRegistry::register_widgets( $widgets_dir, $widget_overrides );

		// Set default widget order if user hasn't customized.
		WidgetRegistry::set_default_widget_order();
}
if ( $is_admin_context ) {
        add_action( 'wp_dashboard_setup', 'vontmnt_dashboard_setup' );
}

/**
 * Initialize cache clearer when cache-clearing options are enabled.
 */
function vontmnt_initialize_cache_clearer(): void {
        $relevant_context = is_admin()
                || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
                || ( defined( 'DOING_CRON' ) && DOING_CRON )
                || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
                || ( defined( 'WP_CLI' ) && WP_CLI )
                || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
                || ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) );

        if ( ! $relevant_context ) {
                return;
        }

        if (
                        Options::is_true( 'clear_caches_hestia' ) ||
                        Options::is_true( 'clear_caches_cloudflare' ) ||
			Options::is_true( 'clear_caches_opcache' )
	) {
				CacheClearer::get_instance();
	}
}
add_action( 'init', 'vontmnt_initialize_cache_clearer' );

/**
 * Run theme updater if enabled.
 */
function vontmnt_run_theme_updater() {
	if ( ! Options::is_true( 'update_themes' ) ) {
		return;
	}
		$theme_updater = new ThemeUpdater();
	$theme_updater->run_updates();
}
add_action( 'vontmnt_theme_updater_check_updates', 'vontmnt_run_theme_updater' );

/**
 * Run plugin updater if enabled.
 */
function vontmnt_run_plugin_updater() {
	if ( ! Options::is_true( 'update_plugins' ) ) {
		return;
	}
		$plugin_updater = new PluginUpdater();
	$plugin_updater->run_updates();
}
add_action( 'vontmnt_plugin_updater_check_updates', 'vontmnt_run_plugin_updater' );

/**
 * Run remote backup if enabled.
 */
function vontmnt_run_remote_backup() {
	if ( ! Options::is_true( 'remote_backups' ) ) {
		return;
	}
		$backup_handler = new RemoteBackup();
	return $backup_handler->create_backup();
}
add_action( 'vontmnt_create_backup', 'vontmnt_run_remote_backup' );

/**
 * Instantiate impersonation only for the 'vontainment' user.
 */
function vontmnt_run_impersonation() {
        if ( ! is_user_logged_in() ) {
                return;
        }

        $admin_bar_available = function_exists( 'is_admin_bar_showing' ) && is_admin_bar_showing();

        if ( ! is_admin() && ! $admin_bar_available ) {
                return;
        }

        $current_user_id = get_current_user_id();
        $has_impersonator = (bool) get_transient( 'vontainment_impersonator_' . $current_user_id );

        if ( Security::is_vontainment_user() || $has_impersonator ) {
                new Impersonation();
        }
}
add_action( 'init', 'vontmnt_run_impersonation' );

/**
 * Delete debug log weekly event handler.
 */
function vontmnt_delete_debug_log_weekly(): void {
		\VWPDashboard\Helpers\DebugLog::delete_log_file();
}
add_action( 'delete_debug_log_weekly_event', 'vontmnt_delete_debug_log_weekly' );

/**
 * Initialize Plugin API if update_key is set.
 */
function vontmnt_initialize_plugin_api(): void {
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
add_action( 'init', 'vontmnt_initialize_plugin_api' );
