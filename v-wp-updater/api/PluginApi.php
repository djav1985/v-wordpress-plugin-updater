<?php
/**
 * Plugin API Service
 *
 * REST API endpoint for listing and installing plugins.
 *
 * @package VWPU
 * @since   2.0.0
 */

namespace VWPU\Api;

use VWPU\Helpers\Logger;
use VWPU\Helpers\Options;
use VWPU\Helpers\SilentUpgraderSkin;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PluginApi
 *
 * Provides REST API endpoints for plugin management.
 *
 * @since 2.0.0
 */
class PluginApi {

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
	private const ROUTE = '/plugins';

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
					'callback'            => array( $this, 'get_plugins' ),
					'permission_callback' => array( $this, 'check_authentication' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_plugin' ),
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
			Logger::error( 'Plugin API authentication failed: Missing API key' );
			return new WP_Error(
				'missing_api_key',
				__( 'API key is required.', 'v-wp-dashboard' ),
				array( 'status' => 401 )
			);
		}

		$stored_key = Options::get( 'update_key' );

		if ( empty( $stored_key ) || ! hash_equals( $stored_key, $api_key ) ) {
			Logger::error( 'Plugin API authentication failed: Invalid API key' );
			return new WP_Error(
				'invalid_api_key',
				__( 'Invalid API key.', 'v-wp-dashboard' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Get list of all installed plugins.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request The REST request (unused).
	 * @return WP_REST_Response|WP_Error Response with plugins list or error.
	 * @phpcsSuppress Generic.CodeAnalysis.UnusedFunctionParameter
	 * @phpcsSuppress VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	 */
	public function get_plugins( WP_REST_Request $request ) {
		unset( $request ); // Parameter required by REST signature.

		try {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins = get_plugins();
			$plugins     = array();

			foreach ( $all_plugins as $plugin_path => $plugin_data ) {
				$plugins[] = array(
					'name'    => $plugin_data['Name'],
					'version' => $plugin_data['Version'],
					'slug'    => dirname( $plugin_path ),
					'file'    => $plugin_path,
					'active'  => is_plugin_active( $plugin_path ),
				);
			}

			Logger::info( 'Plugin API: Listed plugins', array( 'count' => count( $plugins ) ) );

			return new WP_REST_Response(
				array(
					'success' => true,
					'plugins' => $plugins,
					'count'   => count( $plugins ),
				),
				200
			);
		} catch ( \Exception $e ) {
			Logger::error( 'Plugin API: Error listing plugins', array( 'exception' => $e->getMessage() ) );

			return new WP_Error(
				'plugin_list_error',
				__( 'Failed to retrieve plugin list.', 'v-wp-dashboard' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Install a plugin from an uploaded ZIP package.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error Response with installation result or error.
	 */
	public function install_plugin( WP_REST_Request $request ) {
		$file_params = $request->get_file_params();
		$package     = $file_params['package'] ?? null;

		if ( empty( $package ) ) {
			Logger::error( 'Plugin API: Installation failed - missing package upload' );

			return new WP_Error(
				'missing_package',
				__( 'An uploaded plugin ZIP is required.', 'v-wp-dashboard' ),
				array( 'status' => 400 )
			);
		}

		if ( UPLOAD_ERR_OK !== ( $package['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			Logger::error(
				'Plugin API: Installation failed - upload error',
				array( 'code' => $package['error'] ?? null )
			);

			return new WP_Error(
				'upload_error',
				__( 'Failed to upload plugin package.', 'v-wp-dashboard' ),
				array( 'status' => 400 )
			);
		}

		Logger::info(
			'Plugin API: Installing plugin from upload',
			array( 'file_name' => $package['name'] ?? 'unknown' )
		);

		try {
			$package_path = $this->store_uploaded_package( $package );

			if ( is_wp_error( $package_path ) ) {
				Logger::error(
					'Plugin API: Upload handling failed',
					array( 'error' => $package_path->get_error_message() )
				);

				return $package_path;
			}

			// Install the plugin.
			$result = $this->perform_plugin_install( $package_path );

			// Clean up the uploaded file.
			if ( file_exists( $package_path ) ) {
				wp_delete_file( $package_path );
			}

			if ( is_wp_error( $result ) ) {
				Logger::error( 'Plugin API: Installation failed', array( 'error' => $result->get_error_message() ) );

				return new WP_Error(
					'install_failed',
					sprintf(
						/* translators: %s: Error message */
						__( 'Failed to install plugin: %s', 'v-wp-dashboard' ),
						$result->get_error_message()
					),
					array( 'status' => 500 )
				);
			}

			Logger::info( 'Plugin API: Plugin installed successfully' );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Plugin installed successfully.', 'v-wp-dashboard' ),
				),
				200
			);
		} catch ( \Exception $e ) {
			Logger::error( 'Plugin API: Exception during installation', array( 'exception' => $e->getMessage() ) );

			return new WP_Error(
				'plugin_install_error',
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

		$uploaded = wp_handle_upload( $package, $overrides );

		if ( isset( $uploaded['error'] ) ) {
			return new WP_Error( 'upload_error', $uploaded['error'], array( 'status' => 400 ) );
		}

		return $uploaded['file'];
	}

	/**
	 * Perform plugin installation.
	 *
	 * @since 2.0.0
	 * @param string $package_path Path to the plugin package.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function perform_plugin_install( string $package_path ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$skin     = new SilentUpgraderSkin();
		$upgrader = new \Plugin_Upgrader( $skin );

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
			return new WP_Error( 'install_failed', 'Plugin installation failed.' );
		}

		if ( ! empty( $skin->errors ) ) {
			return new WP_Error( 'skin_errors', 'Plugin installation encountered errors.' );
		}

		return true;
	}
}
