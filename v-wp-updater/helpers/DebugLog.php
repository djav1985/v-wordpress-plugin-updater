<?php
/**
 * Debug Log Management Class
 *
 * Handles the deletion and management of the debug.log file.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DebugLog
 *
 * Provides methods for managing the WordPress debug.log file.
 *
 * @since 2.0.0
 */
class DebugLog {

	/**
	 * Deletes the debug.log file.
	 *
	 * Deletes the debug.log file located in the wp-content directory
	 * if it exists.
	 *
	 * @since 2.0.0
	 * @return bool True if file was deleted or didn't exist, false on failure.
	 */
	public static function delete_log_file(): bool {
			$debug_log_path = self::get_log_path();

                if ( file_exists( $debug_log_path ) ) {
                        if ( ! function_exists( 'wp_delete_file' ) ) {
                                require_once ABSPATH . 'wp-admin/includes/file.php';
                        }

                                return wp_delete_file( $debug_log_path );
                }

			return true; // File doesn't exist, consider it successful.
	}

	/**
	 * Get the path to the debug.log file.
	 *
	 * @since 2.0.0
	 * @return string Path to the debug log file.
	 */
	public static function get_log_path(): string {
		if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
				$debug_log = trim( WP_DEBUG_LOG );

			if ( '' !== $debug_log ) {
				return $debug_log;
			}
		}

			return WP_CONTENT_DIR . '/debug.log';
	}

		/**
		 * Check if the debug.log file exists.
		 *
		 * @since 2.0.0
		 * @return bool True if the debug log exists, false otherwise.
		 */
	public static function log_file_exists(): bool {
			return file_exists( self::get_log_path() );
	}
}
