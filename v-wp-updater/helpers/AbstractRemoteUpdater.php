<?php // phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase phpcs:disable WordPress.Files.FileName.InvalidClassFileName
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

		$updateKey = Options::get( $this->get_update_key_option_key() );
		$updateUrl = Options::get( $this->get_update_url_option_key() );

		$updateSuccessful = true;

		foreach ( $this->enumerate_installed_items() as $item ) {
			$installedVersion = $item['version'] ?? '';
			$slug             = $item['slug'] ?? '';

			if ( empty( $slug ) || empty( $installedVersion ) ) {
				$this->log_debug( 'Skipping item with missing slug or version.' );
				$updateSuccessful = false;
				continue;
			}

			$status = $this->process_item_update( $item, $installedVersion, $updateKey, $updateUrl );

			if ( in_array( $status, array( 'error', 'unauthorized' ), true ) ) {
				$updateSuccessful = false;
			}

			$this->log_debug( $slug . ' : ' . $status );
		}

		update_option( $this->get_status_option_name(), $updateSuccessful );

		$message = $updateSuccessful ? $this->get_success_message() : $this->get_error_message();
		set_transient( 'vontmnt_widget_status_message', $message, 30 );
	}

	/**
	 * Process a single item update.
	 *
	 * @param array  $item              Item data.
	 * @param string $installedVersion Currently installed version.
	 * @param string $updateKey        API key used for update requests.
	 * @param string $updateUrl        API base URL.
	 *
	 * @return string Result status.
	 */
	private function process_item_update( array $item, string $installedVersion, string $updateKey, string $updateUrl ): string {
		$response = $this->fetch_package( $item, $installedVersion, $updateKey, $updateUrl );
		$status   = $response['status'] ?? 'error';

		if ( 'update' !== $status ) {
			return $status;
		}

		$downloadUrl = isset( $response['download_url'] ) ? esc_url_raw( $response['download_url'] ) : '';

		if ( empty( $downloadUrl ) ) {
			return 'error';
		}

		$package = $this->download_package( $item['slug'], $downloadUrl );

		if ( is_wp_error( $package ) || ! is_string( $package ) || '' === $package ) {
			return 'error';
		}

		$installResult = $this->perform_install( $item, $package );

		if ( file_exists( $package ) ) {
			wp_delete_file( $package );
		}

		if ( ! $installResult ) {
			return 'error';
		}

		$newVersion = $this->get_current_version( $item );

		if ( ! $newVersion ) {
			return 'error';
		}

		return ( 1 === version_compare( $newVersion, $installedVersion ) ) ? 'updated' : 'error';
	}

	/**
	 * Validate required options.
	 *
	 * @return bool
	 */
	private function validate_required_options(): bool {
		$updateKey = Options::get( $this->get_update_key_option_key() );
		$updateUrl = Options::get( $this->get_update_url_option_key() );

		return ! empty( $updateKey ) && ! empty( $updateUrl );
	}

	/**
	 * Download the remote package to a temporary location.
	 *
	 * @param string $slug         Package slug.
	 * @param string $downloadUrl Remote download URL.
	 *
	 * @return string|WP_Error Path to downloaded package or WP_Error on failure.
	 */
	private function download_package( string $slug, string $downloadUrl ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$uploadDir = wp_upload_dir();
		if ( ! empty( $uploadDir['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $uploadDir['error'] );
		}

		$safeFilename = sanitize_file_name( $slug . '-update.zip' );
		$packagePath  = trailingslashit( $uploadDir['path'] ) . $safeFilename;

		$tmpFile = download_url( $downloadUrl, 300 );

		if ( is_wp_error( $tmpFile ) ) {
			return $tmpFile;
		}

		if ( ! copy( $tmpFile, $packagePath ) ) {
			wp_delete_file( $tmpFile );
			return new WP_Error( 'copy_failed', 'Unable to copy the downloaded package.' );
		}

		wp_delete_file( $tmpFile );

		return $packagePath;
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
	 * @param string $installedVersion Currently installed version.
	 * @param string $updateKey        API key used for update requests.
	 * @param string $updateUrl        API endpoint used for update requests.
	 *
	 * @return array
	 */
	abstract protected function fetch_package( array $item, string $installedVersion, string $updateKey, string $updateUrl ): array;

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
	 * @param string $packagePath Local filesystem path to the downloaded package.
	 *
	 * @return bool True on success, false on failure.
	 */
	abstract protected function perform_install( array $item, string $packagePath ): bool;

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
