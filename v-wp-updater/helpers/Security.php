<?php
/**
 * Security Utility Class
 *
 * Centralized security checks for nonces and capabilities.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Security
 *
 * Provides consistent security checks across the plugin.
 *
 * @since 2.0.0
 */
class Security {

	/**
	 * Verify a GET request with nonce and capability check.
	 *
	 * @since 2.0.0
	 * @param string $action     The action name for nonce verification.
	 * @param string $capability Optional. Required capability. Default 'manage_options'.
	 * @return bool True if verification passed, false otherwise.
	 */
	public static function verify_get_request( string $action, string $capability = 'manage_options' ): bool {
		if (
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $action )
		) {
			return false;
		}

		return current_user_can( $capability );
	}

	/**
	 * Verify a POST request with nonce and capability check.
	 *
	 * @since 2.0.0
	 * @param string $action     The action name for nonce verification.
	 * @param string $capability Optional. Required capability. Default 'manage_options'.
	 * @return bool True if verification passed, false otherwise.
	 */
	public static function verify_post_request( string $action, string $capability = 'manage_options' ): bool {
		if (
			! isset( $_POST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $action )
		) {
			return false;
		}

		return current_user_can( $capability );
	}

	/**
	 * Verify a GET request and die on failure.
	 *
	 * @since 2.0.0
	 * @param string $action     The action name for nonce verification.
	 * @param string $capability Optional. Required capability. Default 'manage_options'.
	 * @return void
	 */
	public static function verify_get_request_or_die( string $action, string $capability = 'manage_options' ): void {
		if ( ! self::verify_get_request( $action, $capability ) ) {
			Logger::error( 'Security check failed for GET request', array( 'action' => $action ) );
			wp_die( esc_html__( 'Security check failed.', 'v-wp-dashboard' ) );
		}
	}

	/**
	 * Verify a POST request and die on failure.
	 *
	 * @since 2.0.0
	 * @param string $action     The action name for nonce verification.
	 * @param string $capability Optional. Required capability. Default 'manage_options'.
	 * @return void
	 */
	public static function verify_post_request_or_die( string $action, string $capability = 'manage_options' ): void {
		if ( ! self::verify_post_request( $action, $capability ) ) {
			Logger::error( 'Security check failed for POST request', array( 'action' => $action ) );
			wp_die( esc_html__( 'Security check failed.', 'v-wp-dashboard' ) );
		}
	}

	/**
	 * Check if the current user has the 'manage_options' capability.
	 *
	 * @since 2.0.0
	 * @return bool True if user can manage options, false otherwise.
	 */
	public static function can_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if the current user is the 'vontainment' user.
	 *
	 * @since 2.0.0
	 * @return bool True if current user is 'vontainment', false otherwise.
	 */
	public static function is_vontainment_user(): bool {
		$current_user = wp_get_current_user();
		return isset( $current_user->user_login ) && 'vontainment' === $current_user->user_login;
	}
}
