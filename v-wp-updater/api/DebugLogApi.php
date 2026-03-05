<?php // phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * DebugLog API Service
 *
 * REST API endpoint for fetching debug log contents.
 *
 * @package VWPU
 * @since   2.0.0
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

	private function __construct() {
		$this->register_routes();
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function register_routes(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

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

	public function check_authentication( WP_REST_Request $request ) {
		$apiKey = $request->get_header( 'X-API-Key' );
		if ( empty( $apiKey ) ) {
			Logger::error( 'DebugLog API authentication failed: Missing API key' );
			return new WP_Error(
				'missing_api_key',
				__( 'API key is required.', 'v-wp-updater' ),
				array( 'status' => 401 )
			);
		}
		$storedKey = Options::get( 'update_key' );
		if ( empty( $storedKey ) || ! hash_equals( $storedKey, $apiKey ) ) {
			Logger::error( 'DebugLog API authentication failed: Invalid API key' );
			return new WP_Error(
				'invalid_api_key',
				__( 'Invalid API key.', 'v-wp-updater' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	public function get_debug_log( WP_REST_Request $request ) {
		$lines = (int) $request->get_param( 'lines' );
		if ( $lines < 1 ) {
			$lines = 100;
		}
		$logPath = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? WP_DEBUG_LOG : ABSPATH . 'wp-content/debug.log';
		if ( ! file_exists( $logPath ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Debug log file not found.',
					'log'     => array(),
				),
				404
			);
		}
		$logLines = $this->tail_file( $logPath, $lines );
		return new WP_REST_Response(
			array(
				'success' => true,
				'lines'   => $lines,
				'log'     => $logLines,
			),
			200
		);
	}

	private function tail_file( string $filepath, int $lines ): array {
		$buffer = 4096;
		$f      = fopen( $filepath, 'rb' );
		if ( false === $f ) {
			return array();
		}
		fseek( $f, 0, SEEK_END );
		$pos       = ftell( $f );
		$data      = '';
		$lineCount = 0;
		while ( $pos > 0 && $lineCount <= $lines ) {
			$readSize = ( $pos - $buffer > 0 ) ? $buffer : $pos;
			$pos     -= $readSize;
			fseek( $f, $pos );
			$chunk     = fread( $f, $readSize );
			$data      = $chunk . $data;
			$lineCount = substr_count( $data, "\n" );
		}
		fclose( $f );
		$linesArr = explode( "\n", $data );
		return array_slice( $linesArr, -$lines );
	}
}
