<?php

/**
 * Theme Updater
 *
 * Handles the updating of WordPress themes.
 *
 * @package V_WP_Updater
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class V_WP_Updater_Theme_Updater
 *
 * Handles theme updates via the Vontainment API.
 *
 * @since 1.0.0
 */
class V_WP_Updater_Theme_Updater {


	/**
	 * Runs updates for all installed themes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run_updates(): void {
		// Check if theme updates are enabled.
		if ( ! v_updater_option_is_true( 'update_themes' ) ) {
			return;
		}

		include_once ABSPATH . 'wp-admin/includes/theme.php';

		if ( ! $this->validate_constants() ) {
			if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
				error_log( 'Missing theme update constants.' );
			}
			return;
		}

		$update_successful = true;
		$themes            = wp_get_themes();

		foreach ( $themes as $theme_slug => $theme ) {
			$installed_version = $theme->get( 'Version' );

			$result      = $this->update_theme( $theme_slug, $installed_version );
			$log_message = $theme_slug . ' : ' . $result['reason'];

			if ( 'error' === $result['reason'] || 'unauthorized' === $result['reason'] ) {
				$update_successful = false;
			}

			if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
				error_log( $log_message );
			}
		}

		update_option( 'v_updater_theme_update_status', $update_successful );
		$message = $update_successful
			? __( '✅ Themes updated successfully!', 'v-wp-updater' )
			: __( '❌ Error updating themes.', 'v-wp-updater' );
		set_transient( 'v_updater_widget_status_message', $message, 30 );
	}

	/**
	 * Validate required options are set.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function validate_constants(): bool {
		$update_key = v_updater_get_option( 'update_key' );
		$update_url = v_updater_get_option( 'update_theme_url' );
		return ! empty( $update_key ) && ! empty( $update_url );
	}

	/**
	 * Update a single theme.
	 *
	 * @since 1.0.0
	 * @param string $theme_slug        Theme slug.
	 * @param string $installed_version Installed version.
	 * @return array {
	 *     @type string $reason Result status: updated|no_update|unauthorized|error.
	 * }
	 */
	private function update_theme( string $theme_slug, string $installed_version ): array {
		$api_url = add_query_arg(
			array(
				'type'    => 'theme',
				'domain'  => rawurlencode( wp_parse_url( site_url(), PHP_URL_HOST ) ),
				'slug'    => rawurlencode( $theme_slug ),
				'version' => rawurlencode( $installed_version ),
				'key'     => v_updater_get_option( 'update_key' ),
			),
			v_updater_get_option( 'update_theme_url' )
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'sslverify' => true,
				'timeout'   => 30,
			)
		);
		if ( is_wp_error( $response ) ) {
			return array( 'reason' => 'error' );
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( 204 === $http_code ) {
			return array( 'reason' => 'no_update' );
		}
		if ( 401 === $http_code ) {
			return array( 'reason' => 'unauthorized' );
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		$upgrade_result = $this->perform_theme_upgrade( $theme_slug, $response_data['zip_url'] );

		// After upgrade, check installed version.
		$themes      = wp_get_themes();
		$new_version = isset( $themes[ $theme_slug ] ) ? $themes[ $theme_slug ]->get( 'Version' ) : null;

		if (
			'updated' === $upgrade_result['reason'] &&
			$new_version &&
			version_compare( $new_version, $installed_version, '>' )
		) {
			return array( 'reason' => 'updated' );
		}

		return array( 'reason' => 'error' );
	}

	/**
	 * Perform the actual theme upgrade.
	 *
	 * @since 1.0.0
	 * @param string $theme_slug   Theme slug.
	 * @param string $download_url Download URL.
	 * @return array {
	 *     @type string $reason Result status: updated|error.
	 * }
	 */
	private function perform_theme_upgrade( string $theme_slug, string $download_url ): array {
		$download_url = esc_url_raw( $download_url );
		if ( empty( $download_url ) ) {
			return array( 'reason' => 'error' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return array( 'reason' => 'error' );
		}

		$safe_filename  = sanitize_file_name( $theme_slug . '-update.zip' );
		$theme_zip_file = $upload_dir['path'] . '/' . $safe_filename;

		$tmp_file = download_url( $download_url, 300 );
		if ( is_wp_error( $tmp_file ) ) {
			return array( 'reason' => 'error' );
		}

		if ( ! copy( $tmp_file, $theme_zip_file ) ) {
			wp_delete_file( $tmp_file );
			return array( 'reason' => 'error' );
		}
		wp_delete_file( $tmp_file );

		return $this->install_theme_upgrade( $theme_slug, $theme_zip_file );
	}

	/**
	 * Install the theme upgrade using WordPress upgrader.
	 *
	 * @since 1.0.0
	 * @param string $theme_slug     Theme slug.
	 * @param string $theme_zip_file Path to zip file.
	 * @return array {
	 *     @type string $reason Result status: updated|error.
	 * }
	 */
	private function install_theme_upgrade( string $theme_slug, string $theme_zip_file ): array {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( ! class_exists( 'V_WP_Updater_Silent_Upgrader_Skin' ) ) {
			include_once __DIR__ . '/class-v-wp-updater-silent-upgrader-skin.php';
		}

		$skin     = new V_WP_Updater_Silent_Upgrader_Skin();
		$upgrader = new Theme_Upgrader( $skin );

		$filter_callback = fn( $reply, $package) => ( $package === $theme_zip_file ) ? $theme_zip_file : $reply;
		add_filter( 'upgrader_pre_download', $filter_callback, 10, 2 );

		$result = $upgrader->install(
			$theme_zip_file,
			array(
				'clear_update_cache' => true,
				'overwrite_package'  => true,
			)
		);

		remove_filter( 'upgrader_pre_download', $filter_callback, 10 );
		if ( file_exists( $theme_zip_file ) ) {
			wp_delete_file( $theme_zip_file );
		}

		if ( is_wp_error( $result ) || false === $result || ! empty( $skin->errors ) ) {
			return array( 'reason' => 'error' );
		}
		return array( 'reason' => 'updated' );
	}
}
