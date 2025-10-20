<?php
/**
 * Plugin Name: WP Updator By Vontainment
 * Plugin URI:  https://vontainment.com
 * Description: Wordpress plugin and theme updater
 * Version:     2.0.0
 * Author:      Vontainment
 * Author URI:  https://vontainment.com
 * License:     MIT
 * Text Domain: v-wp-updater
 *
 * @package V_WP_Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

/**
 * Runs on plugin activation.
 *
 * Schedules updates and downloads WP-CLI. Schedules plugin and theme updates, debug log deletion, and remote backups.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_dashboard_activate(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	try {
		$install_file = plugin_dir_path( __FILE__ ) . 'install.php';
		if ( file_exists( $install_file ) ) {
			include_once $install_file;
			if ( function_exists( 'vontmnt_plugin_updater_schedule_updates' ) ) {
				vontmnt_plugin_updater_schedule_updates();
			}
			if ( function_exists( 'vontmnt_theme_updater_schedule_updates' ) ) {
				vontmnt_theme_updater_schedule_updates();
			}
			if ( function_exists( 'vontmnt_add_custom_constants_to_wp_config' ) ) {
				vontmnt_add_custom_constants_to_wp_config();
			}
		}
	} catch ( Exception $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Activation error: ' . esc_html( $e->getMessage() ) );
		}
	}
}
register_activation_hook( __FILE__, 'vontmnt_dashboard_activate' );

/**
 * Runs on plugin deactivation/uninstall.
 *
 * Clears scheduled tasks for plugin and theme updates, debug log deletion, and backups.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_dashboard_cleanup(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	try {
		$uninstall_file = __DIR__ . '/uninstall.php';
		if ( file_exists( $uninstall_file ) ) {
			include_once $uninstall_file;
			if ( function_exists( 'vontmnt_clear_plugin_update_schedule' ) ) {
				vontmnt_clear_plugin_update_schedule();
			}
			if ( function_exists( 'vontmnt_clear_theme_update_schedule' ) ) {
				vontmnt_clear_theme_update_schedule();
			}
		}
	} catch ( Exception $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Deactivation error: ' . esc_html( $e->getMessage() ) );
		}
	}
}
register_deactivation_hook( __FILE__, 'vontmnt_dashboard_cleanup' );
register_uninstall_hook( __FILE__, 'vontmnt_dashboard_cleanup' );

/**
 * Sets up dashboard widgets and styles.
 *
 * Adds custom dashboard widgets and styles for the WordPress admin dashboard.
 *
 * @since 1.0.0
 * @return void
 */
function vontmnt_dashboard_setup(): void {
	// Remove inline CSS, styles will be enqueued below.

	// Access the global $wp_meta_boxes variable to manipulate dashboard widgets.
	global $wp_meta_boxes;

	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );

	// Settings widget include (only if present).
	$widget_dir = __DIR__ . '/widgets/';
	if ( file_exists( $widget_dir . 'settings.php' ) ) {
		include_once $widget_dir . 'settings.php';
	}

	// Register settings widget for users with manage_options capability.
	if ( function_exists( 'vontmnt_widget_settings_display' ) && current_user_can( 'manage_options' ) ) {
		wp_add_dashboard_widget( 'vontmnt_widget_settings', __( 'v-wp-updater Settings', 'v-wp-updater' ), 'vontmnt_widget_settings_display' );
	}
}

/**
 * Enqueue admin dashboard styles for V_WP_Updater widgets.
 *
 * @param string $hook The current admin page hook.
 */
function vontmnt_dashboard_admin_styles( string $hook ): void {
	if ( 'index.php' === $hook ) {
		$css_file = __DIR__ . '/assets/styles.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'v-wp-updater-admin',
				plugin_dir_url( __FILE__ ) . 'assets/styles.css',
				array(),
				filemtime( $css_file )
			);
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'v-wp-updater: CSS file not found: ' . $css_file );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'vontmnt_dashboard_admin_styles' );

if ( vontmnt_option_is_true( 'update_themes' ) ) {
	$theme_updater_file = __DIR__ . '/includes/class-v-wp-dashboard-theme-updater.php';
	if ( file_exists( $theme_updater_file ) ) {
		include_once $theme_updater_file;
		if ( class_exists( 'V_WP_Dashboard_Theme_Updater' ) ) {
			add_action(
				'vontmnt_theme_updater_check_updates',
				function () {
					$theme_updater = new V_WP_Dashboard_Theme_Updater();
					$theme_updater->run_updates();
				}
			);
		}
	}
}

if ( vontmnt_option_is_true( 'update_plugins' ) ) {
	$plugin_updater_file = __DIR__ . '/includes/class-v-wp-dashboard-plugin-updater.php';
	if ( file_exists( $plugin_updater_file ) ) {
		include_once $plugin_updater_file;
		if ( class_exists( 'V_WP_Dashboard_Plugin_Updater' ) ) {
			add_action(
				'vontmnt_plugin_updater_check_updates',
				function () {
					$plugin_updater = new V_WP_Dashboard_Plugin_Updater();
					$plugin_updater->run_updates();
				}
			);
		}
	}
}
