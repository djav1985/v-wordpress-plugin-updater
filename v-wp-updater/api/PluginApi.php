<?php
/**
 * Plugin API Service
 *
 * REST API endpoint for listing and installing plugins.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Api;

use VWPDashboard\Helpers\Logger;
use VWPDashboard\Helpers\Options;
use VWPDashboard\Helpers\SilentUpgraderSkin;
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
			'/plugins',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_plugins' ),
				'permission_callback' => array( $this, 'check_authentication' ),
			)
		);

		register_rest_route(
			'v-wp-dashboard/v1',
			'/install-plugin',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'install_plugin' ),
				'permission_callback' => array( $this, 'check_authentication' ),
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
	 * Get the list of plugins.
	 *
	 * @since 2.0.0
	 *
	 * @return WP_REST_Response|WP_Error Response with plugins list or error.
	 */
	public function get_plugins(): WP_REST_Response|WP_Error {
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
	 * Install a new plugin.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return WP_REST_Response|WP_Error Response with installation result or error.
	 */
	public function install_plugin( WP_REST_Request $request ) {
		$files         = $request->get_file_params();
		$package_field = $files['package'] ?? null;
		$filename      = '';

		if ( is_array( $package_field ) && isset( $package_field['name'] ) ) {
			$filename = sanitize_file_name( (string) $package_field['name'] );
		}

		if ( ! is_array( $package_field ) || empty( $package_field['tmp_name'] ) ) {
			return new WP_Error(
				'missing_package_file',
				__( 'A plugin package upload is required.', 'v-wp-dashboard' ),
				array( 'status' => 400 )
			);
		}

		if ( isset( $package_field['error'] ) && 0 !== (int) $package_field['error'] ) {
			return new WP_Error(
				'package_upload_error',
				__( 'Plugin package upload failed.', 'v-wp-dashboard' ),
				array( 'status' => 400 )
			);
		}

		Logger::info( 'Plugin API: Installing plugin', array( 'filename' => $filename ) );

		try {
			$package_path = $this->store_uploaded_plugin_package( $package_field );

			if ( is_wp_error( $package_path ) ) {
				Logger::error( 'Plugin API: Package validation failed', array( 'error' => $package_path->get_error_message() ) );

				return new WP_Error(
					'package_validation_failed',
					sprintf(
						/* translators: %s: Error message */
						__( 'Failed to process plugin package: %s', 'v-wp-dashboard' ),
						$package_path->get_error_message()
					),
					array( 'status' => 400 )
				);
			}

			$result = $this->perform_plugin_install( $package_path );

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
	 * Persist the uploaded plugin package to the uploads directory.
	 *
	 * @since 2.0.3
	 * @param array $package_field Uploaded file data from the REST request.
	 * @return string|WP_Error Path to stored package or WP_Error on failure.
	 */
	private function store_uploaded_plugin_package( array $package_field ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $upload_dir['error'] );
		}

		if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
			return new WP_Error( 'upload_dir_error', 'Unable to create uploads directory.' );
		}

		$original_name = isset( $package_field['name'] ) ? sanitize_file_name( (string) $package_field['name'] ) : '';
		if ( '' === $original_name ) {
			$original_name = 'plugin-' . time() . '.zip';
		}

		if ( '.zip' !== strtolower( substr( $original_name, -4 ) ) ) {
			$original_name .= '.zip';
		}

		$filename     = wp_unique_filename( $upload_dir['path'], $original_name );
		$package_path = trailingslashit( $upload_dir['path'] ) . $filename;

		if ( empty( $package_field['tmp_name'] ) || ! file_exists( $package_field['tmp_name'] ) ) {
			return new WP_Error( 'invalid_upload', 'Uploaded plugin package could not be found.' );
		}

		if ( ! copy( $package_field['tmp_name'], $package_path ) ) {
			return new WP_Error( 'copy_failed', 'Unable to store the uploaded plugin package.' );
		}

		$file_type = wp_check_filetype_and_ext( $package_path, basename( $package_path ), array( 'zip' => 'application/zip' ) );
		if ( empty( $file_type['ext'] ) || 'zip' !== $file_type['ext'] ) {
			wp_delete_file( $package_path );
			return new WP_Error( 'invalid_file_type', 'Uploaded file is not a valid ZIP archive.' );
		}

		return $package_path;
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
