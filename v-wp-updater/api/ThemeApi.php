<?php
/**
 * Theme API Service
 *
 * REST API endpoint for listing and installing themes.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Api;

use VWPDashboard\Helpers\Logger;
use VWPDashboard\Helpers\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

/**
 * Class ThemeApi
 *
 * Provides REST API endpoints for theme management.
 *
 * @since 2.0.0
 */
class ThemeApi {

		/**
		 * Singleton instance.
		 *
		 * @since 2.0.0
		 * @var self|null
		 */
	private static ?self $instance = null;

		/**
		 * REST API namespace.
		 *
		 * @since 2.0.0
		 * @var string
		 */
	private const NAMESPACE = 'vwpd/v1';

		/**
		 * REST API route.
		 *
		 * @since 2.0.0
		 * @var string
		 */
	private const ROUTE = '/themes';

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
				self::NAMESPACE,
				self::ROUTE,
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_themes' ),
						'permission_callback' => array( $this, 'check_authentication' ),
					),
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'install_theme' ),
						'permission_callback' => array( $this, 'check_authentication' ),
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
				Logger::error( 'Theme API authentication failed: Missing API key' );
			return new WP_Error(
				'missing_api_key',
				__( 'API key is required.', 'v-wp-dashboard' ),
				array( 'status' => 401 )
			);
		}
			$stored_key = Options::get( 'update_key' );
		if ( empty( $stored_key ) || ! hash_equals( $stored_key, $api_key ) ) {
			Logger::error( 'Theme API authentication failed: Invalid API key' );
			return new WP_Error(
				'invalid_api_key',
				__( 'Invalid API key.', 'v-wp-dashboard' ),
				array( 'status' => 401 )
			);
		}
			return true;
	}

		/**
		 * Get list of all installed themes.
		 *
		 * @since 2.0.0
		 * @param WP_REST_Request $request The REST request (unused).
		 * @return WP_REST_Response|WP_Error Response with themes list or error.
		 */
	public function get_themes( WP_REST_Request $request ) {
			unset( $request ); // Parameter required by REST signature.

		try {
			if ( ! function_exists( 'wp_get_themes' ) ) {
				require_once ABSPATH . 'wp-includes/theme.php';
			}
				$all_themes = wp_get_themes();
				$themes     = array();
			foreach ( $all_themes as $theme_slug => $theme_obj ) {
				$themes[] = array(
					'name'    => $theme_obj->get( 'Name' ),
					'version' => $theme_obj->get( 'Version' ),
					'slug'    => $theme_slug,
					'active'  => ( wp_get_theme()->get_stylesheet() === $theme_slug ),
				);
			}
				Logger::info( 'Theme API: Listed themes', array( 'count' => count( $themes ) ) );
				return new WP_REST_Response(
					array(
						'success' => true,
						'themes'  => $themes,
						'count'   => count( $themes ),
					),
					200
				);
		} catch ( \Exception $e ) {
			Logger::error( 'Theme API: Error listing themes', array( 'exception' => $e->getMessage() ) );
			return new WP_Error(
				'theme_list_error',
				__( 'Failed to retrieve theme list.', 'v-wp-dashboard' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Install a theme from an uploaded ZIP package.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error Response with installation result or error.
	 */
	public function install_theme( WP_REST_Request $request ) {
		$file_params = $request->get_file_params();
		$package     = $file_params['package'] ?? null;

		if ( empty( $package ) ) {
			Logger::error( 'Theme API: Installation failed - missing package upload' );

			return new WP_Error(
				'missing_package',
				__( 'An uploaded theme ZIP is required.', 'v-wp-dashboard' ),
				array( 'status' => 400 )
			);
		}

		if ( UPLOAD_ERR_OK !== ( $package['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			Logger::error(
				'Theme API: Installation failed - upload error',
				array( 'code' => $package['error'] ?? null )
			);

			return new WP_Error(
				'upload_error',
				__( 'Failed to upload theme package.', 'v-wp-dashboard' ),
				array( 'status' => 400 )
			);
		}

		Logger::info(
			'Theme API: Installing theme from upload',
			array( 'file_name' => $package['name'] ?? 'unknown' )
		);

		try {
			$package_path = $this->store_uploaded_package( $package );
			if ( is_wp_error( $package_path ) ) {
				Logger::error( 'Theme API: Upload handling failed', array( 'error' => $package_path->get_error_message() ) );

				return $package_path;
			}
			$result = $this->perform_theme_install( $package_path );
			if ( file_exists( $package_path ) ) {
				wp_delete_file( $package_path );
			}
			if ( is_wp_error( $result ) ) {
				Logger::error( 'Theme API: Installation failed', array( 'error' => $result->get_error_message() ) );
					return new WP_Error(
						'install_failed',
						sprintf(
							/* translators: %s: Error message */
							__( 'Failed to install theme: %s', 'v-wp-dashboard' ),
							$result->get_error_message()
						),
						array( 'status' => 500 )
					);
			}
			Logger::info( 'Theme API: Theme installed successfully' );
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Theme installed successfully.', 'v-wp-dashboard' ),
				),
				200
			);
		} catch ( \Exception $e ) {
			Logger::error( 'Theme API: Exception during installation', array( 'exception' => $e->getMessage() ) );
			return new WP_Error(
				'theme_install_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'An error occurred: %s', 'v-wp-dashboard' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Move an uploaded package into a managed location.
	 *
	 * @since 2.0.0
	 * @param array $package Uploaded file data from the REST request.
	 * @return string|WP_Error Path to stored package or WP_Error on failure.
	 */
	private function store_uploaded_package( array $package ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $upload_dir['error'] );
		}
		$overrides = array(
			'test_form' => false,
			'mimes'     => array( 'zip' => 'application/zip' ),
		);
		$uploaded  = wp_handle_upload( $package, $overrides );
		if ( isset( $uploaded['error'] ) ) {
			return new WP_Error( 'upload_error', $uploaded['error'], array( 'status' => 400 ) );
		}

		return $uploaded['file'];
	}

		/**
		 * Perform theme installation.
		 *
		 * @since 2.0.0
		 * @param string $package_path Path to the theme package.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
	private function perform_theme_install( string $package_path ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			$skin        = new \WP_Upgrader_Skin();
			$upgrader    = new \Theme_Upgrader( $skin );
		$filter_callback = static function ( $reply, $package ) use ( $package_path ) {
			return ( $package === $package_path ) ? $package_path : $reply;
		};
			add_filter( 'upgrader_pre_download', $filter_callback, 10, 2 );
			$result = $upgrader->install(
				$package_path,
				array(
					'clear_update_cache' => true,
					'overwrite_package'  => true,
				)
			);
		remove_filter( 'upgrader_pre_download', $filter_callback, 10 );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( false === $result ) {
			return new WP_Error( 'install_failed', 'Theme installation failed.' );
		}
		if ( ! empty( $skin->errors ) ) {
			return new WP_Error( 'skin_errors', 'Theme installation encountered errors.' );
		}
		return true;
	}
}
