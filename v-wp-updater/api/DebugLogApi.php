<?php
/**
 * DebugLog API Service
 *
 * REST API endpoint for fetching debug log contents.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Api;

use VWPDashboard\Helpers\DebugLog;
use VWPDashboard\Helpers\Logger;
use VWPDashboard\Helpers\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DebugLogApi
 *
 * Provides REST API endpoints for debug log management.
 *
 * @since 2.0.0
 */
class DebugLogApi {

	/**
	 * Singleton instance.
	 *
	 * @since 2.0.0
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		$this->register_routes();
	}

	/**
	 * Get singleton instance.
	 *
	 * @since 2.0.0
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_routes(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST routes callback.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'v-wp-dashboard/v1',
			'/debug-log',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_debug_log' ),
				'permission_callback' => array( $this, 'check_authentication' ),
				'args'                => array(
					'lines' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 100,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && $param > 0 && $param <= 10000;
						},
					),
				),
			)
		);
	}

	/**
	 * Check API authentication.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if authenticated, WP_Error otherwise.
	 */
	public function check_authentication( WP_REST_Request $request ) {
		$api_key = $request->get_header( 'X-API-Key' );

		if ( empty( $api_key ) ) {
			Logger::error( 'DebugLog API authentication failed: Missing API key' );
			return new WP_Error(
				'missing_api_key',
				__( 'API key is required.', 'v-wp-dashboard' ),
				array( 'status' => 401 )
			);
		}

		$stored_key = Options::get( 'update_key' );

		if ( empty( $stored_key ) || ! hash_equals( $stored_key, $api_key ) ) {
			Logger::error( 'DebugLog API authentication failed: Invalid API key' );
			return new WP_Error(
				'invalid_api_key',
				__( 'Invalid API key.', 'v-wp-dashboard' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Get the debug log contents.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return WP_REST_Response|WP_Error Response with log contents or error.
	 */
	public function get_debug_log( WP_REST_Request $request ) {
		$lines = $request->get_param( 'lines' );

		Logger::info( 'DebugLog API: Get debug log endpoint called', array( 'lines' => $lines ) );

		try {
                        // Get the debug log file path.
                        $log_file = DebugLog::get_log_path();

			// Check if the log file exists.
			if ( ! file_exists( $log_file ) ) {
				Logger::info( 'DebugLog API: Debug log file not found' );
				return new WP_Error(
					'file_not_found',
					__( 'Debug log file not found.', 'v-wp-dashboard' ),
					array( 'status' => 404 )
				);
			}

			// Read the log file using WordPress filesystem API.
			require_once ABSPATH . 'wp-admin/includes/file.php';

			if ( ! WP_Filesystem() ) {
				Logger::error( 'DebugLog API: Failed to initialize WP filesystem' );
				return new WP_Error(
					'filesystem_error',
					__( 'Could not initialize filesystem.', 'v-wp-dashboard' ),
					array( 'status' => 500 )
				);
			}

			global $wp_filesystem;
			$log_contents = $wp_filesystem->get_contents( $log_file );

			if ( false === $log_contents ) {
				Logger::error( 'DebugLog API: Failed to read debug log file' );
				return new WP_Error(
					'read_error',
					__( 'Could not read debug log file.', 'v-wp-dashboard' ),
					array( 'status' => 500 )
				);
			}

			if ( $lines > 0 ) {
				$log_lines    = explode( "\n", $log_contents );
				$total_lines  = count( $log_lines );
				$start_line   = max( 0, $total_lines - $lines );
				$log_lines    = array_slice( $log_lines, $start_line );
				$log_contents = implode( "\n", $log_lines );
			}

			Logger::info( 'DebugLog API: Debug log retrieved successfully' );

			return new WP_REST_Response(
				array(
					'success'  => true,
					'contents' => $log_contents,
					'lines'    => $lines,
				),
				200
			);
		} catch ( \Exception $e ) {
			Logger::error( 'DebugLog API: Exception while reading debug log', array( 'exception' => $e->getMessage() ) );

			return new WP_Error(
				'debug_log_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'An error occurred: %s', 'v-wp-dashboard' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}
}
