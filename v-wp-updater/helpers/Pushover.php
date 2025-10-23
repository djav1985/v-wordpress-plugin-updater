<?php
/**
 * Pushover Notification Utility Class
 *
 * Handles sending notifications via the Pushover service.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pushover
 *
 * Sends push notifications via Pushover API.
 *
 * @since 2.0.0
 */
class Pushover {

	/**
	 * Pushover API endpoint.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const API_URL = 'https://api.pushover.net/1/messages.json';

	/**
	 * Send a Pushover notification.
	 *
	 * @since 2.0.0
	 * @param string $message  The message to send.
	 * @param int    $priority Optional. The priority level (-2 to 2). Default 0.
	 * @return bool True on success, false on failure.
	 */
	public static function send( string $message, int $priority = 0 ): bool {
		$token = Options::get( 'pushover_token' );
		$user  = Options::get( 'pushover_user' );

		if ( empty( $token ) || empty( $user ) ) {
			Logger::debug( 'Pushover notification skipped: Missing token or user.' );
			return false;
		}

		$site_title = get_bloginfo( 'name' );

		$response = wp_remote_post(
			self::API_URL,
			array(
				'body'    => array(
					'token'    => $token,
					'user'     => $user,
					'message'  => $message,
					'title'    => $site_title,
					'priority' => $priority,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::error(
				'Pushover notification failed',
				array( 'error' => $response->get_error_message() )
			);
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			Logger::error(
				'Pushover API returned non-200 status',
				array( 'status_code' => $response_code )
			);
			return false;
		}

		Logger::debug( 'Pushover notification sent successfully', array( 'message' => $message ) );
		return true;
	}
}
