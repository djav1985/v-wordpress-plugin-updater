<?php
/**
 * Options Utility Class
 *
 * Helper class for managing plugin options with namespace support.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Options
 *
 * Centralized option management with automatic prefixing and validation.
 *
 * @since 2.0.0
 */
class Options {

	/**
	 * Option prefix for all plugin options.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private const PREFIX = 'vontmnt_';

	/**
	 * Get an option value with automatic prefixing.
	 *
	 * @since 2.0.0
	 * @param string $option_key     The option key without the prefix.
	 * @param mixed  $default_value  Optional. Default value to return if the option does not exist.
	 * @return mixed The option value or default.
	 */
	public static function get( string $option_key, $default_value = '' ) {
		return get_option( self::PREFIX . $option_key, $default_value );
	}

	/**
	 * Set an option value with automatic prefixing.
	 *
	 * @since 2.0.0
	 * @param string $option_key  The option key without the prefix.
	 * @param mixed  $value       The value to set.
	 * @param bool   $autoload    Optional. Whether to autoload the option. Default false.
	 * @return bool True if option was updated, false otherwise.
	 */
	public static function set( string $option_key, $value, bool $autoload = false ): bool {
		return update_option( self::PREFIX . $option_key, $value, $autoload ? 'yes' : 'no' );
	}

	/**
	 * Delete an option with automatic prefixing.
	 *
	 * @since 2.0.0
	 * @param string $option_key The option key without the prefix.
	 * @return bool True if option was deleted, false otherwise.
	 */
	public static function delete( string $option_key ): bool {
		return delete_option( self::PREFIX . $option_key );
	}

	/**
	 * Check if a plugin option is true (with automatic prefixing).
	 *
	 * @since 2.0.0
	 * @param string $option_key Option key without prefix.
	 * @return bool True if option is set and truthy.
	 */
	public static function is_true( string $option_key ): bool {
		$value = self::get( $option_key, 'false' );
		if ( is_bool( $value ) ) {
			return $value;
		}
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Initialize default plugin options.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function initialize_defaults(): void {
		$defaults = array(
			'clear_caches_hestia'     => 'false',
			'clear_caches_cloudflare' => 'false',
			'cloudflare_email'        => '',
			'cloudflare_zone_id'      => '',
			'cloudflare_api_key'      => '',
			'clear_caches_opcache'    => 'false',
			'update_plugins'          => 'false',
			'update_themes'           => 'false',
			'update_key'              => '',
			'update_plugin_url'       => 'https://wp-updates.servicesbyv.com/plugins/api.php',
			'update_theme_url'        => 'https://wp-updates.servicesbyv.com/themes/api.php',
			'remote_backups'          => 'false',
			'remote_backups_timing'   => '',
			'ssh_host'                => '',
			'ssh_port'                => '',
			'ssh_user'                => '',
			'ssh_password'            => '',
			'remote_backup_dir'       => '',
			'max_backups'             => '5',
			'pushover_token'          => '',
			'pushover_user'           => '',
		);

		foreach ( $defaults as $option_name => $default_value ) {
			// Only add if option doesn't exist yet.
			if ( false === get_option( self::PREFIX . $option_name, false ) ) {
				add_option( self::PREFIX . $option_name, $default_value, '', 'no' );
			}
		}
	}
}
