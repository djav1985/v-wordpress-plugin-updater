<?php // phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Logger Utility Class
 *
 * Centralized logging functionality with consistent error handling.
 *
 * @package VWPU
 * @since   2.0.0
 */

namespace VWPU\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logger
 *
 * Provides consistent logging across the plugin when WP_DEBUG is enabled.
 *
 * @since 2.0.0
 */
class Logger {

	/**
	 * Log a debug message.
	 *
	 * Only logs if WP_DEBUG is enabled.
	 *
	 * @since 2.0.0
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function debug( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$formatted_message = self::format_message( 'DEBUG', $message, $context );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $formatted_message );
	}

	/**
	 * Log an info message.
	 *
	 * Only logs if WP_DEBUG is enabled.
	 *
	 * @since 2.0.0
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function info( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$formatted_message = self::format_message( 'INFO', $message, $context );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $formatted_message );
	}

	/**
	 * Log an error message.
	 *
	 * Only logs if WP_DEBUG is enabled.
	 *
	 * @since 2.0.0
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data.
	 * @return void
	 */
	public static function error( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$formatted_message = self::format_message( 'ERROR', $message, $context );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $formatted_message );
	}

	/**
	 * Format a log message with level and context.
	 *
	 * @since 2.0.0
	 * @param string $level   The log level (DEBUG, INFO, ERROR, etc.).
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data.
	 * @return string The formatted message.
	 */
	private static function format_message( string $level, string $message, array $context = array() ): string {
		$formatted = sprintf( '[v-wp-updater] [%s] %s', $level, $message );

		if ( ! empty( $context ) ) {
			$formatted .= ' | Context: ' . wp_json_encode( $context );
		}

		return $formatted;
	}
}
