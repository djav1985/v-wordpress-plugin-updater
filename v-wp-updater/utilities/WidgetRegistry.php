<?php
/**
 * Widget Registry Class
 *
 * Handles automatic widget discovery and registration.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Utilities;

use VWPDashboard\Helpers\Logger;
use VWPDashboard\Helpers\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WidgetRegistry
 *
 * Automates widget registration by scanning the widgets directory.
 *
 * @since 2.0.0
 */
class WidgetRegistry {

	/**
	 * Widget configuration array.
	 *
	 * Maps widget files to their registration data.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private static $widgets = array(
		'status.php'   => array(
			'id'       => 'vontmnt_widget_status',
			'title'    => 'Vontainment Premium Services',
			'callback' => 'vontmnt_widget_status_display',
		),
		'services.php' => array(
			'id'       => 'vontmnt_widget_services',
			'title'    => 'Services By V',
			'callback' => 'vontmnt_widget_services_display',
		),
		'support.php'  => array(
			'id'       => 'vontmnt_widget_support',
			'title'    => 'Vontainment Support',
			'callback' => 'vontmnt_widget_support_display',
		),
		'logs.php'     => array(
			'id'       => 'vwplogs_widget',
			'title'    => 'Debug Log (Last 25 Lines)',
			'callback' => 'vwplogs_widget_display',
		),
		'settings.php' => array(
			'id'           => 'vontmnt_widget_settings',
			'title'        => 'V-WP-Dashboard Settings',
			'callback'     => 'vontmnt_widget_settings_display',
			'vontmnt_only' => true,
		),
	);

	/**
	 * Register all dashboard widgets.
	 *
	 * Automatically includes widget files and registers them if their
	 * display functions exist.
	 *
	 * @since 2.0.0
	 * @param string $widgets_dir Path to the widgets directory.
	 * @param array  $overrides   Optional map of widget filenames to a boolean flag indicating whether
	 *                            they should be registered. A value of false prevents registration.
	 * @return void
	 */
	public static function register_widgets( string $widgets_dir, array $overrides = array() ): void {
		foreach ( self::$widgets as $filename => $widget_config ) {
			if ( array_key_exists( $filename, $overrides ) && false === $overrides[ $filename ] ) {
				continue;
			}

				$filepath = $widgets_dir . $filename;

				// Include the widget file if it exists.
			if ( file_exists( $filepath ) ) {
					include_once $filepath;
			}

				// Skip registration if callback doesn't exist.
			if ( ! function_exists( $widget_config['callback'] ) ) {
				Logger::debug(
					'Widget callback not found, skipping registration',
					array( 'widget' => $widget_config['id'] )
				);
				continue;
			}

				// Check if widget is restricted to vontainment user.
			if ( isset( $widget_config['vontmnt_only'] ) && $widget_config['vontmnt_only'] ) {
				if ( ! Security::is_vontainment_user() ) {
					continue;
				}
			}

				// Register the widget.
				wp_add_dashboard_widget(
					$widget_config['id'],
					$widget_config['title'], // Already translated in widget files.
					$widget_config['callback']
				);

			Logger::debug( 'Widget registered successfully', array( 'widget' => $widget_config['id'] ) );
		}
	}

	/**
	 * Remove default WordPress dashboard widgets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function remove_default_widgets(): void {
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
	}

	/**
	 * Set default widget order if user hasn't customized.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function set_default_widget_order(): void {
		global $wp_meta_boxes;

		$user_id    = get_current_user_id();
		$meta_key   = 'meta-box-order_dashboard';
		$user_order = get_user_meta( $user_id, $meta_key, true );

		if ( ! empty( $user_order ) ) {
			return; // User has customized, don't override.
		}

		// Set default order for normal column.
		if (
			isset(
				$wp_meta_boxes['dashboard']['normal']['core']['vontmnt_widget_services'],
				$wp_meta_boxes['dashboard']['normal']['core']['vontmnt_widget_status']
			)
		) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required to set default dashboard widget order.
			$wp_meta_boxes['dashboard']['normal']['core'] = array(
				'vontmnt_widget_services' => $wp_meta_boxes['dashboard']['normal']['core']['vontmnt_widget_services'],
				'vontmnt_widget_status'   => $wp_meta_boxes['dashboard']['normal']['core']['vontmnt_widget_status'],
			);
		}

		// Set default order for side column.
		if (
			isset(
				$wp_meta_boxes['dashboard']['side']['core']['vontmnt_widget_support'],
				$wp_meta_boxes['dashboard']['side']['core']['vwplogs_widget']
			)
		) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required to set default dashboard widget order.
			$wp_meta_boxes['dashboard']['side']['core'] = array(
				'vontmnt_widget_support' => $wp_meta_boxes['dashboard']['side']['core']['vontmnt_widget_support'],
				'vwplogs_widget'         => $wp_meta_boxes['dashboard']['side']['core']['vwplogs_widget'],
			);
		}
	}
}
