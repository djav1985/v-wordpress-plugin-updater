<?php // phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 2.0.0
 *
 * File: ThemeUpdater.php
 * Description: V WordPress Plugin Updater
 */

namespace VWPU\Services;

use VWPU\Helpers\SilentUpgraderSkin;
use VWPU\Helpers\AbstractRemoteUpdater;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ThemeUpdater
 *
 * Handles theme updates via the Vontainment API.
 *
 * @since 1.0.0
 */
class ThemeUpdater extends AbstractRemoteUpdater {

	/**
	 * Path to the ZIP file written during the update-check request.
	 *
	 * Set by fetch_package() when the API returns HTTP 200 so that
	 * download_package() can reuse the already-downloaded archive instead of
	 * making a second HTTP request.
	 *
	 * @var string|null
	 */
	private ?string $prefetched_package = null;

	/**
	 * Prepare required includes for theme updates.
	 *
	 * @return void
	 */
	protected function prepare_environment(): void {
		include_once ABSPATH . 'wp-admin/includes/theme.php';
	}

	/**
	 * Fetch the remote package metadata for a theme.
	 *
	 * When the API returns HTTP 200 the response body (the ZIP archive) is
	 * written to a temp file immediately so that download_package() can reuse
	 * it, avoiding a second full download of the same archive.
	 *
	 * @param array  $item              Theme metadata.
	 * @param string $installed_version Installed version string.
	 * @param string $update_key        API key used for requests.
	 * @param string $update_url        API endpoint used for requests.
	 *
	 * @return array
	 */
	protected function fetch_package( array $item, string $installed_version, string $update_key, string $update_url ): array {
		$api_url = add_query_arg(
			array(
				'type'    => 'theme',
				'domain'  => rawurlencode( wp_parse_url( site_url(), PHP_URL_HOST ) ),
				'slug'    => rawurlencode( $item['slug'] ),
				'version' => rawurlencode( $installed_version ),
				'key'     => $update_key,
			),
			$update_url
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'sslverify' => true,
				'timeout'   => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'status' => 'error' );
		}

		$http_code = wp_remote_retrieve_response_code( $response );

		if ( 204 === $http_code ) {
			return array( 'status' => 'no_update' );
		}

		if ( 403 === $http_code ) {
			return array( 'status' => 'unauthorized' );
		}

		if ( 200 !== $http_code ) {
			return array( 'status' => 'error' );
		}

		// Save the response body to disk now so download_package() can return
		// this file directly, skipping a second HTTP request for the archive.

		// Validate slug before using it in a file path to prevent traversal.
		if ( 1 !== preg_match( '/^[a-zA-Z0-9_\-]+$/', $item['slug'] ) ) {
			$this->log_debug( 'Rejected slug with unsafe characters: ' . $item['slug'] );
			return array( 'status' => 'error' );
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return array( 'status' => 'error' );
		}

		$safe_filename = sanitize_file_name( $item['slug'] . '-update.zip' );
		$package_path  = trailingslashit( $upload_dir['path'] ) . $safe_filename;
		$zip_body      = wp_remote_retrieve_body( $response );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $package_path, $zip_body ) ) {
			$error     = error_get_last();
			$error_msg = isset( $error['message'] ) ? $error['message'] : 'unknown error';
			$this->log_debug( 'Failed to write prefetched ZIP for ' . $item['slug'] . ': ' . $error_msg );
			return array( 'status' => 'error' );
		}

		$this->prefetched_package = $package_path;

		return array(
			'status'       => 'update',
			'download_url' => $api_url,
		);
	}

	/**
	 * Return the pre-fetched ZIP if available, otherwise fall back to a fresh download.
	 *
	 * {@inheritdoc}
	 *
	 * @param string $slug         Package slug.
	 * @param string $download_url Remote download URL (used only when no prefetched file exists).
	 *
	 * @return string|\WP_Error
	 */
	protected function download_package( string $slug, string $download_url ) {
		if ( null !== $this->prefetched_package ) {
			$path                     = $this->prefetched_package;
			$this->prefetched_package = null;
			return $path;
		}

		return parent::download_package( $slug, $download_url );
	}

	/**
	 * Enumerate installed themes.
	 *
	 * @return iterable
	 */
	protected function enumerate_installed_items(): iterable {
		$themes = wp_get_themes();

		foreach ( $themes as $theme_slug => $theme ) {
			yield array(
				'slug'    => $theme_slug,
				'version' => $theme->get( 'Version' ),
			);
		}
	}

	/**
	 * Perform a theme installation using the WordPress upgrader.
	 *
	 * @param array  $item         Theme metadata.
	 * @param string $package_path Local path to the downloaded package.
	 *
	 * @return bool
	 */
	protected function perform_install( array $item, string $package_path ): bool {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

				$skin = new SilentUpgraderSkin();
		$upgrader     = new \Theme_Upgrader( $skin );

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

		return ! ( is_wp_error( $result ) || false === $result || ! empty( $skin->errors ) );
	}

	/**
	 * Retrieve the current version of a theme.
	 *
	 * @param array $item Theme metadata.
	 *
	 * @return string|null
	 */
	protected function get_current_version( array $item ): ?string {
		$themes = wp_get_themes();

		if ( isset( $themes[ $item['slug'] ] ) ) {
			return $themes[ $item['slug'] ]->get( 'Version' );
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_update_url_option_key(): string {
		return 'update_theme_url';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_status_option_name(): string {
		return 'vontmnt-thup';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_success_message(): string {
		return __( '✅ Themes updated successfully!', 'v-wp-updater' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_error_message(): string {
		return __( '❌ Error updating themes.', 'v-wp-updater' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_missing_configuration_message(): string {
		return 'Missing theme update constants.';
	}
}
