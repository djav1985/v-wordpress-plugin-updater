<?php
/**
 * Ajax Handler Class
 *
 * Handles AJAX and admin-post requests for the plugin.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Utilities;

use VWPDashboard\Services\CacheClearer;
use VWPDashboard\Services\PluginUpdater;
use VWPDashboard\Services\ThemeUpdater;
use VWPDashboard\Helpers\Security;
use VWPDashboard\Helpers\Logger;
use VWPDashboard\Helpers\DebugLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ajax
 *
 * Handles admin-post actions with security checks.
 *
 * @since 2.0.0
 */
class Ajax {

	/**
	 * Register all admin-post handlers.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function register_handlers(): void {
		add_action( 'admin_post_vontmnt_clear_caches', array( __CLASS__, 'handle_clear_caches' ) );
		add_action( 'admin_post_vontmnt_update_plugins', array( __CLASS__, 'handle_update_plugins' ) );
		add_action( 'admin_post_vontmnt_update_themes', array( __CLASS__, 'handle_update_themes' ) );
		add_action( 'admin_post_vontmnt_delete_log', array( __CLASS__, 'handle_delete_log' ) );
	}

	/**
	 * Admin post handler for clearing caches.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function handle_clear_caches(): void {
		Security::verify_post_request_or_die( 'clear_caches_action' );

		Logger::info( 'Clear caches action triggered via admin-post' );

				$success = CacheClearer::get_instance()->flush_caches();

		if ( $success && false === get_transient( 'vontmnt_widget_status_message' ) ) {
				set_transient( 'vontmnt_widget_status_message', __( '✅ All caches cleared successfully!', 'v-wp-dashboard' ), 30 );
		}
		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Admin post handler for updating plugins.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function handle_update_plugins(): void {
		Security::verify_post_request_or_die( 'update_plugins_action' );

		Logger::info( 'Update plugins action triggered via admin-post' );

				$plugin_updater = new PluginUpdater();
				$plugin_updater->run_updates();
		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Admin post handler for updating themes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function handle_update_themes(): void {
		Security::verify_post_request_or_die( 'update_themes_action' );

		Logger::info( 'Update themes action triggered via admin-post' );

				$theme_updater = new ThemeUpdater();
				$theme_updater->run_updates();
		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Admin post handler for deleting debug log.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function handle_delete_log(): void {
		Security::verify_post_request_or_die( 'delete_log_action' );

		Logger::info( 'Delete log action triggered via admin-post' );

		DebugLog::delete_log_file();
		set_transient( 'vontmnt_widget_status_message', __( 'Debug log deleted successfully!', 'v-wp-dashboard' ), 30 );
		wp_safe_redirect( admin_url() );
		exit;
	}
}
