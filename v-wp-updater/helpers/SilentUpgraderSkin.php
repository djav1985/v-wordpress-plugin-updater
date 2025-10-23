<?php
/**
 * Silent Upgrader Skin for WordPress Updates.
 *
 * @package VWPU
 * @since 1.0.0
 */

namespace VWPU\Helpers;

use WP_Error;
use WP_Upgrader_Skin;

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

/**
 * Silent Upgrader Skin for WordPress Plugin and Theme Updates.
 *
 * Suppresses output during plugin and theme upgrades, capturing messages and errors internally.
 *
 * @since 1.0.0
 * @see WP_Upgrader_Skin
 */
class SilentUpgraderSkin extends WP_Upgrader_Skin {
	/**
	 * Stores feedback messages.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $messages = array();

	/**
	 * Stores error messages.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $errors = array();

	/**
	 * Overrides the header method to suppress output.
	 *
	 * @since 1.0.0
	 */
	public function header() {}

	/**
	 * Overrides the footer method to suppress output.
	 *
	 * @since 1.0.0
	 */
	public function footer() {}

	/**
	 * Captures feedback messages.
	 *
	 * @since 1.0.0
	 * @param string $msg The feedback message.
	 * @param mixed  ...$args Optional arguments for vsprintf.
	 */
	public function feedback( $msg, ...$args ) {
		if ( $args ) {
			$msg = vsprintf( $msg, $args );
		}
		$this->messages[] = $msg;
	}

	/**
	 * Captures error messages.
	 *
	 * @since 1.0.0
	 * @param string|WP_Error $errors The error(s).
	 */
	public function error( $errors ) {
		if ( is_wp_error( $errors ) ) {
			foreach ( $errors->get_error_messages() as $msg ) {
				$this->errors[] = $msg;
			}
		} elseif ( is_string( $errors ) ) {
			$this->errors[] = $errors;
		}
	}

	/**
	 * Overrides the before action to suppress output.
	 *
	 * @since 1.0.0
	 */
	public function before() {}

	/**
	 * Overrides the after action to suppress output.
	 *
	 * @since 1.0.0
	 */
	public function after() {}
}
