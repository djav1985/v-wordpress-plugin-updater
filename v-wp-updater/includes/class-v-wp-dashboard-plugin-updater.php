<?php

/**
 * Plugin Updater
 *
 * Handles the updating of WordPress plugins.
 *
 * @package V_WP_Updater
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Class V_WP_Updater_Plugin_Updater
 *
 * Handles plugin updates via the Vontainment API.
 *
 * @since 1.0.0
 */
class V_WP_Updater_Plugin_Updater
{

	/**
	 * Runs updates for all installed plugins.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run_updates(): void
	{
		// Check if plugin updates are enabled.
		if ( ! v_updater_option_is_true( 'update_plugins' ) ) {
			return;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if (! $this->validate_constants()) {
			if (defined('WP_DEBUG') && true === WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log('Missing plugin update constants.');
			}
			return;
		}

		$update_successful = true;
		$plugins           = get_plugins();

		foreach ($plugins as $plugin_path => $plugin) {
			$plugin_slug       = dirname($plugin_path);
			$installed_version = $plugin['Version'];

			$result = $this->update_plugin($plugin_slug, $installed_version, $plugin_path);
			$log_message = $plugin_slug . ' : ' . $result['reason'];

			if ($result['reason'] === 'error' || $result['reason'] === 'unauthorized') {
				$update_successful = false;
			}

			if (defined('WP_DEBUG') && true === WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log($log_message);
			}
		}

		update_option('v_updater_plugin_update_status', $update_successful);
		$message = $update_successful
			? __('✅ Plugins updated successfully!', 'v-wp-updater')
			: __('❌ Error updating plugins.', 'v-wp-updater');
		set_transient('v_updater_widget_status_message', $message, 30);
	}

	/**
	 * Validate required options.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function validate_constants(): bool
	{
		$update_key = v_updater_get_option( 'update_key' );
		$update_url = v_updater_get_option( 'update_plugin_url' );
		return ! empty( $update_key ) && ! empty( $update_url );
	}

	/**
	 * Update a single plugin.
	 *
	 * @since 1.0.0
	 * @param string $plugin_slug       Plugin slug.
	 * @param string $installed_version Installed version.
	 * @param string $plugin_path       Plugin path.
	 * @return array {
	 *     @type string $reason Result status: updated|no_update|unauthorized|error.
	 * }
	 */
	private function update_plugin(string $plugin_slug, string $installed_version, string $plugin_path): array
	{
		$api_url = add_query_arg(
			array(
				'type'    => 'plugin',
				'domain'  => rawurlencode(wp_parse_url(site_url(), PHP_URL_HOST)),
				'slug'    => rawurlencode($plugin_slug),
				'version' => rawurlencode($installed_version),
				'key'     => v_updater_get_option( 'update_key' ),
			),
			v_updater_get_option( 'update_plugin_url' )
		);

		$response = wp_remote_get($api_url, array('sslverify' => true, 'timeout' => 30));
		if (is_wp_error($response)) {
			return ['reason' => 'error'];
		}

		$http_code = wp_remote_retrieve_response_code($response);
		if ($http_code === 204) {
			return ['reason' => 'no_update'];
		}
		if ($http_code === 401) {
			return ['reason' => 'unauthorized'];
		}

		$response_body = wp_remote_retrieve_body($response);
		$response_data = json_decode($response_body, true);

		// Run upgrader.
		$upgrade_result = $this->perform_plugin_upgrade($plugin_slug, $response_data['zip_url'], $plugin_path);

		// Double-check version after install.
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins     = get_plugins();
		$new_version = $plugins[$plugin_path]['Version'] ?? null;

		if (
			$upgrade_result['reason'] === 'updated' &&
			$new_version &&
			version_compare($new_version, $installed_version, '>')
		) {
			return ['reason' => 'updated'];
		}

		return ['reason' => 'error'];
	}

	/**
	 * Perform plugin upgrade preparation and delegate to installer.
	 *
	 * @since 1.0.0
	 * @param string $plugin_slug  Plugin slug.
	 * @param string $download_url Download URL.
	 * @param string $plugin_path  Plugin path.
	 * @return array {
	 *     @type string $reason Result status: updated|error.
	 * }
	 */
	private function perform_plugin_upgrade(string $plugin_slug, string $download_url, string $plugin_path): array
	{
		$download_url = esc_url_raw($download_url);
		if (empty($download_url)) {
			return ['reason' => 'error'];
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload_dir = wp_upload_dir();
		if (! empty($upload_dir['error'])) {
			return ['reason' => 'error'];
		}

		$safe_filename   = sanitize_file_name($plugin_slug . '-update.zip');
		$plugin_zip_file = $upload_dir['path'] . '/' . $safe_filename;

		$tmp_file = download_url($download_url, 300);
		if (is_wp_error($tmp_file)) {
			return ['reason' => 'error'];
		}

		if (! copy($tmp_file, $plugin_zip_file)) {
			wp_delete_file($tmp_file);
			return ['reason' => 'error'];
		}
		wp_delete_file($tmp_file);

		return $this->install_plugin_upgrade($plugin_slug, $plugin_zip_file, $plugin_path);
	}

	/**
	 * Install plugin upgrade using WordPress upgrader.
	 *
	 * @since 1.0.0
	 * @param string $plugin_slug     Plugin slug.
	 * @param string $plugin_zip_file Path to zip file.
	 * @param string $plugin_path     Plugin path.
	 * @return array {
	 *     @type string $reason Result status: updated|error.
	 * }
	 */
	private function install_plugin_upgrade(string $plugin_slug, string $plugin_zip_file, string $plugin_path): array
	{
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if (! class_exists('V_WP_Updater_Silent_Upgrader_Skin')) {
			include_once __DIR__ . '/class-v-wp-updater-silent-upgrader-skin.php';
		}

		$skin     = new V_WP_Updater_Silent_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader($skin);

		$filter_callback = fn($reply, $package) => ($package === $plugin_zip_file) ? $plugin_zip_file : $reply;
		add_filter('upgrader_pre_download', $filter_callback, 10, 2);

		$result = $upgrader->install(
			$plugin_zip_file,
			array(
				'clear_update_cache' => true,
				'overwrite_package'  => true,
			)
		);

		remove_filter('upgrader_pre_download', $filter_callback, 10);
		if (file_exists($plugin_zip_file)) {
			wp_delete_file($plugin_zip_file);
		}

		if (is_wp_error($result) || $result === false || !empty($skin->errors)) {
			return ['reason' => 'error'];
		}
		return ['reason' => 'updated'];
	}
}
