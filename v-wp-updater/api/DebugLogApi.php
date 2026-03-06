<?php // phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 2.0.0
 *
 * File: DebugLogApi.php
 * Description: V WordPress Plugin Updater
 */

namespace VWPU\Api;

use VWPU\Helpers\Logger;
use VWPU\Helpers\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DebugLogApi {
	private static ?self $instance = null;
	private const NAMESPACE        = 'vwpd/v1';
	private const ROUTE            = '/debuglog';

	/**
	 * Register the REST API routes on construction.
	 */
	private function __construct() {
		$this->register_routes();
	}

	/**
	 * Return the singleton DebugLogApi instance, creating it on first call.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook the REST route registration into the WordPress rest_api_init action.
	 *
	 * @return void
	 */
	private function register_routes(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the /debuglog REST route with WordPress.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_debug_log' ),
					'permission_callback' => array( $this, 'check_authentication' ),
					'args'                => array(
						'lines' => array(
							'required' => false,
							'type'     => 'integer',
							'default'  => 100,
						),
					),
				),
			)
		);
	}

	/**
	 * Permission callback: verify the X-API-Key header against the stored update key.
	 *
	 * @param WP_REST_Request $request The incoming REST request.
	 * @return true|WP_Error True on success, WP_Error on authentication failure.
	 */
	public function check_authentication( WP_REST_Request $request ) {
		$api_key = $request->get_header( 'X-API-Key' );
		if ( empty( $api_key ) ) {
			Logger::error( 'DebugLog API authentication failed: Missing API key' );
			return new WP_Error(
				'missing_api_key',
				__( 'API key is required.', 'v-wp-updater' ),
				array( 'status' => 401 )
			);
		}
		$stored_key = Options::get( 'update_key' );
		if ( empty( $stored_key ) || ! hash_equals( $stored_key, $api_key ) ) {
			Logger::error( 'DebugLog API authentication failed: Invalid API key' );
			return new WP_Error(
				'invalid_api_key',
				__( 'Invalid API key.', 'v-wp-updater' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Return the last N lines of the WordPress debug log.
	 *
	 * @param WP_REST_Request $request REST request; accepts optional 'lines' integer param.
	 * @return WP_REST_Response
	 */
	public function get_debug_log( WP_REST_Request $request ) {
		$lines = (int) $request->get_param( 'lines' );
		if ( $lines < 1 ) {
			$lines = 100;
		}
		$log_path = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? WP_DEBUG_LOG : ABSPATH . 'wp-content/debug.log';
		if ( ! file_exists( $log_path ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Debug log file not found.',
					'log'     => array(),
				),
				404
			);
		}
		$log_lines = $this->tail_file( $log_path, $lines );
		return new WP_REST_Response(
			array(
				'success' => true,
				'lines'   => $lines,
				'log'     => $log_lines,
			),
			200
		);
	}

	/**
	 * Read the last N lines from a file without loading the whole file into memory.
	 *
	 * @param string $filepath Absolute path to the file to read.
	 * @param int    $lines    Number of lines to return from the end of the file.
	 * @return array<int, string> Array of lines, or an empty array on failure.
	 */
	private function tail_file( string $filepath, int $lines ): array {
		$buffer = 4096;
		$f      = fopen( $filepath, 'rb' );
		if ( false === $f ) {
			return array();
		}
		fseek( $f, 0, SEEK_END );
		$pos        = ftell( $f );
		$data       = '';
		$line_count = 0;
		while ( $pos > 0 && $line_count <= $lines ) {
			$read_size = ( $pos - $buffer > 0 ) ? $buffer : $pos;
			$pos      -= $read_size;
			fseek( $f, $pos );
			$chunk      = fread( $f, $read_size );
			$data       = $chunk . $data;
			$line_count = substr_count( $data, "\n" );
		}
		fclose( $f );
		$lines_arr = explode( "\n", $data );
		return array_slice( $lines_arr, -$lines );
	}
}
