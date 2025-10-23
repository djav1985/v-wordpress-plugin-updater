<?php
/**
 * Abstract Remote Updater
 *
 * Provides a template method workflow for remote updates.
 *
 * @package VWPU
 */

namespace VWPU\Helpers;

use VWPU\Helpers\Options;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AbstractRemoteUpdater
 *
 * Implements the shared workflow for remote updates.
 */
abstract class AbstractRemoteUpdater {

	/**
	 * Run updates for the configured resource type.
	 *
	 * @return void
	 */
	public function run_updates(): void {
		$this->prepare_environment();

		if ( ! $this->validate_required_options() ) {
			$this->log_debug( $this->get_missing_configuration_message() );
			return;
		}

		$update_key = Options::get( $this->get_update_key_option_key() );
		$update_url = Options::get( $this->get_update_url_option_key() );

		$update_successful = true;

		foreach ( $this->enumerate_installed_items() as $item ) {
			$installed_version = $item['version'] ?? '';
			$slug              = $item['slug'] ?? '';

			if ( empty( $slug ) || empty( $installed_version ) ) {
				$this->log_debug( 'Skipping item with missing slug or version.' );
				$update_successful = false;
				continue;
			}

			$status = $this->process_item_update( $item, $installed_version, $update_key, $update_url );

			if ( in_array( $status, array( 'error', 'unauthorized' ), true ) ) {
				$update_successful = false;
			}

			$this->log_debug( $slug . ' : ' . $status );
		}

		update_option( $this->get_status_option_name(), $update_successful );

		$message = $update_successful ? $this->get_success_message() : $this->get_error_message();
		set_transient( 'vontmnt_widget_status_message', $message, 30 );
	}

	/**
	 * Process a single item update.
	 *
	 * @param array  $item              Item data.
	 * @param string $installed_version Currently installed version.
	 * @param string $update_key        API key used for update requests.
	 * @param string $update_url        API base URL.
	 *
	 * @return string Result status.
	 */
	private function process_item_update( array $item, string $installed_version, string $update_key, string $update_url ): string {
		$response = $this->fetch_package( $item, $installed_version, $update_key, $update_url );
		$status   = $response['status'] ?? 'error';

		if ( 'update' !== $status ) {
			return $status;
		}

		$download_url = isset( $response['download_url'] ) ? esc_url_raw( $response['download_url'] ) : '';

		if ( empty( $download_url ) ) {
			return 'error';
		}

		$package = $this->download_package( $item['slug'], $download_url );

		if ( is_wp_error( $package ) || ! is_string( $package ) || '' === $package ) {
			return 'error';
		}

		$install_result = $this->perform_install( $item, $package );

		if ( file_exists( $package ) ) {
			wp_delete_file( $package );
		}

		if ( ! $install_result ) {
			return 'error';
		}

		$new_version = $this->get_current_version( $item );

		if ( ! $new_version ) {
			return 'error';
		}

		return ( 1 === version_compare( $new_version, $installed_version ) ) ? 'updated' : 'error';
	}

	/**
	 * Validate required options.
	 *
	 * @return bool
	 */
	private function validate_required_options(): bool {
		$update_key = Options::get( $this->get_update_key_option_key() );
		$update_url = Options::get( $this->get_update_url_option_key() );

		return ! empty( $update_key ) && ! empty( $update_url );
	}

	/**
	 * Download the remote package to a temporary location.
	 *
	 * @param string $slug         Package slug.
	 * @param string $download_url Remote download URL.
	 *
	 * @return string|WP_Error Path to downloaded package or WP_Error on failure.
	 */
	private function download_package( string $slug, string $download_url ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $upload_dir['error'] );
		}

		$safe_filename = sanitize_file_name( $slug . '-update.zip' );
		$package_path  = trailingslashit( $upload_dir['path'] ) . $safe_filename;

		$tmp_file = download_url( $download_url, 300 );

		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}

		if ( ! copy( $tmp_file, $package_path ) ) {
			wp_delete_file( $tmp_file );
			return new WP_Error( 'copy_failed', 'Unable to copy the downloaded package.' );
		}

		wp_delete_file( $tmp_file );

		return $package_path;
	}

	/**
	 * Log debug information when WP_DEBUG is enabled.
	 *
	 * @param string $message Debug message.
	 *
	 * @return void
	 */
	protected function log_debug( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $message );
		}
	}

	/**
	 * Prepare the environment before running updates.
	 *
	 * @return void
	 */
	protected function prepare_environment(): void {
		// Default implementation does nothing.
	}

	/**
	 * Build the API request and return the response payload.
	 *
	 * Expected return format:
	 *   array(
	 *     'status'       => 'update'|'no_update'|'unauthorized'|'error',
	 *     'download_url' => 'https://example.com/package.zip', // Required when status === 'update'.
	 *   )
	 *
	 * @param array  $item              Item metadata from enumerate_installed_items().
	 * @param string $installed_version Currently installed version.
	 * @param string $update_key        API key used for update requests.
	 * @param string $update_url        API endpoint used for update requests.
	 *
	 * @return array
	 */
	abstract protected function fetch_package( array $item, string $installed_version, string $update_key, string $update_url ): array;

	/**
	 * Enumerate installed items.
	 *
	 * Each item MUST include at minimum:
	 * - slug (string)
	 * - version (string)
	 *
	 * @return iterable
	 */
	abstract protected function enumerate_installed_items(): iterable;

	/**
	 * Perform the installation routine using the downloaded package.
	 *
	 * @param array  $item         Item metadata.
	 * @param string $package_path Local filesystem path to the downloaded package.
	 *
	 * @return bool True on success, false on failure.
	 */
	abstract protected function perform_install( array $item, string $package_path ): bool;

	/**
	 * Retrieve the currently installed version for the given item after an update.
	 *
	 * @param array $item Item metadata.
	 *
	 * @return string|null
	 */
	abstract protected function get_current_version( array $item ): ?string;

	/**
	 * Option key used to retrieve the remote update URL.
	 *
	 * @return string
	 */
	abstract protected function get_update_url_option_key(): string;

	/**
	 * Option key used to retrieve the shared update key.
	 *
	 * @return string
	 */
	protected function get_update_key_option_key(): string {
		return 'update_key';
	}

	/**
	 * Option name used to store overall update status.
	 *
	 * @return string
	 */
	abstract protected function get_status_option_name(): string;

	/**
	 * Success message stored in the status transient.
	 *
	 * @return string
	 */
	abstract protected function get_success_message(): string;

	/**
	 * Error message stored in the status transient.
	 *
	 * @return string
	 */
	abstract protected function get_error_message(): string;

	/**
	 * Message logged when required configuration is missing.
	 *
	 * @return string
	 */
	abstract protected function get_missing_configuration_message(): string;
}
