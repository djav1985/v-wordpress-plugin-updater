<?php
/**
 * Impersonation
 *
 * Handles impersonation for Vontinment
 *
 * @package V_WP_Dashboard
 * @since 1.0.0
 */

namespace VWPDashboard\Services;

use WP_Admin_Bar;
use WP_User;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
		exit; // Prevent direct access to this file for security.
}

/**
 * Class Impersonation
 *
 * Handles user impersonation functionality for Vontainment administrators.
 *
 * @since 1.0.0
 */
class Impersonation {

	/**
	 * Initialize the impersonation functionality.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks(): void {
			add_filter( 'user_row_actions', array( $this, 'add_login_as_action' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'handle_impersonation_requests' ) );
			add_action( 'admin_bar_menu', array( $this, 'add_return_to_original_user_link' ), 100 );
	}

	/**
	 * Add "Login as" link for Vontainment users.
	 *
	 * @since 1.0.0
	 * @param array   $actions     User actions.
	 * @param WP_User $user_object User object.
	 * @return array Modified actions.
	 */
	public function add_login_as_action( array $actions, WP_User $user_object ): array {
			$current_user = wp_get_current_user();

		$cap = apply_filters( 'v_wpd_switch_users_cap', 'switch_users' );
		if ( ! current_user_can( $cap ) || $user_object->ID === $current_user->ID ) {
				return $actions;
		}

			$url = wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'vontainment_login_as',
						'user_id' => $user_object->ID,
					),
					admin_url()
				),
				'vontainment_login_as_' . $user_object->ID
			);

			$actions['vontainment_login_as'] = '<a href="' . esc_url( $url ) . '">Login as</a>';
			return $actions;
	}

	/**
	 * Add "Return to original user" link to the admin bar when impersonating.
	 *
	 * @since 1.0.0
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public function add_return_to_original_user_link( WP_Admin_Bar $wp_admin_bar ): void {
		$current_user_id = get_current_user_id();
		$original_id     = get_transient( 'vontainment_impersonator_' . $current_user_id );

		if ( ! $original_id ) {
			return;
		}

		$url = wp_nonce_url(
			add_query_arg( 'vontainment_logout', 1, admin_url() ),
			'vontainment_logout_' . $current_user_id
		);

		$wp_admin_bar->add_node(
			array(
				'id'    => 'vontainment_logout',
				'title' => __( 'Return to original user', 'v-wp-dashboard' ),
				'href'  => esc_url( $url ),
			)
		);
	}

	/**
	 * Handle impersonation and logout requests.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_impersonation_requests(): void {
		if ( ! is_user_logged_in() ) {
				return;
		}

				// Handle impersonation request.
		if (
							'vontainment_login_as' === sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) ) &&
							isset( $_GET['action'], $_GET['user_id'], $_GET['_wpnonce'] ) &&
							current_user_can( apply_filters( 'v_wpd_switch_users_cap', 'switch_users' ) ) &&
							wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'vontainment_login_as_' . absint( wp_unslash( $_GET['user_id'] ) ) )
					) {
			$target_user_id = absint( wp_unslash( $_GET['user_id'] ) );
			if ( get_current_user_id() !== $target_user_id ) {
				set_transient( 'vontainment_impersonator_' . $target_user_id, get_current_user_id(), 3600 );
				wp_set_auth_cookie( $target_user_id );
				wp_safe_redirect( admin_url() );
				exit;
			}
		}

			// Handle return to original user request.
		if ( isset( $_GET['vontainment_logout'], $_GET['_wpnonce'] ) ) {
						$current_id  = get_current_user_id();
						$original_id = get_transient( 'vontainment_impersonator_' . $current_id );

			if (
									$original_id &&
									wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'vontainment_logout_' . $current_id ) &&
									user_can( $original_id, apply_filters( 'v_wpd_switch_users_cap', 'switch_users' ) )
							) {
						delete_transient( 'vontainment_impersonator_' . $current_id );
						wp_set_auth_cookie( $original_id );
						wp_safe_redirect( admin_url() );
						exit;
			}
		}
	}
}
