<?php
/**
 * Plugin Updater
 *
 * Handles the updating of WordPress plugins.
 *
 * @package VWPU
 * @since 1.0.0
 */

namespace VWPU\Services;

use VWPU\Helpers\SilentUpgraderSkin;
use VWPU\Helpers\AbstractRemoteUpdater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PluginUpdater
 *
 * Handles plugin updates via the Vontainment API.
 *
 * @since 1.0.0
 */
class PluginUpdater extends AbstractRemoteUpdater {

	/**
	 * Prepare required includes for plugin updates.
	 *
	 * @return void
	 */
	protected function prepare_environment(): void {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	/**
	 * Fetch the remote package metadata for a plugin.
	 *
	 * @param array  $item              Plugin metadata.
	 * @param string $installed_version Installed version string.
	 * @param string $update_key        API key used for requests.
	 * @param string $update_url        API endpoint used for requests.
	 *
	 * @return array
	 */
	protected function fetch_package( array $item, string $installed_version, string $update_key, string $update_url ): array {
		$api_url = add_query_arg(
			array(
				'domain'  => rawurlencode( wp_parse_url( site_url(), PHP_URL_HOST ) ),
				'plugin'  => rawurlencode( $item['slug'] ),
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

		if ( 401 === $http_code ) {
			return array( 'status' => 'unauthorized' );
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( empty( $response_data['zip_url'] ) ) {
			return array( 'status' => 'error' );
		}

		return array(
			'status'       => 'update',
			'download_url' => $response_data['zip_url'],
		);
	}

	/**
	 * Enumerate installed plugins.
	 *
	 * @return iterable
	 */
	protected function enumerate_installed_items(): iterable {
		$plugins = get_plugins();

		foreach ( $plugins as $plugin_path => $plugin ) {
			yield array(
				'slug'      => dirname( $plugin_path ),
				'version'   => $plugin['Version'],
				'file_path' => $plugin_path,
			);
		}
	}

	/**
	 * Perform a plugin installation using the WordPress upgrader.
	 *
	 * @param array  $item         Plugin metadata.
	 * @param string $package_path Local path to the downloaded package.
	 *
	 * @return bool
	 */
	protected function perform_install( array $item, string $package_path ): bool {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

				$skin = new SilentUpgraderSkin();
		$upgrader     = new \Plugin_Upgrader( $skin );

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
	 * Retrieve the current version of a plugin.
	 *
	 * @param array $item Plugin metadata.
	 *
	 * @return string|null
	 */
	protected function get_current_version( array $item ): ?string {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugins = get_plugins();

		return $plugins[ $item['file_path'] ]['Version'] ?? null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_update_url_option_key(): string {
		return 'update_plugin_url';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_status_option_name(): string {
		return 'vontmnt-plup';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_success_message(): string {
		return __( '✅ Plugins updated successfully!', 'v-wp-dashboard' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_error_message(): string {
		return __( '❌ Error updating plugins.', 'v-wp-dashboard' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_missing_configuration_message(): string {
		return 'Missing plugin update constants.';
	}
}
