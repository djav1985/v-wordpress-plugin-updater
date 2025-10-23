<?php
/**
 * Admin widget to display the last 25 lines of the debug log.
 *
 * @package V_WP_Dashboard
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the last 25 lines of the debug log in the dashboard widget.
 *
 * @return void
 */
function vwplogs_widget_display(): void {
	// Get the path to the debug log file.
	if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && WP_DEBUG_LOG !== '' ) {
		$log_file = WP_DEBUG_LOG;
	} else {
		$log_file = WP_CONTENT_DIR . '/debug.log';
	}
	echo '<div class="vwp-widget vwp-widget-log">';
	if ( file_exists( $log_file ) ) {
			$lines = vwplogs_tail( $log_file, 25 );
		if ( $lines ) {
				echo '<div class="vwp-log-container">' . nl2br( esc_html( implode( "\n", $lines ) ) ) . '</div>';
		} else {
					echo '<div class="vwp-log-container">' . esc_html__( 'Log file is empty.', 'v-wp-dashboard' ) . '</div>';
		}
	} else {
			echo '<div class="vwp-log-container">' . esc_html__( 'Debug log not found: ', 'v-wp-dashboard' ) . esc_html( $log_file ) . '</div>';
	}
		echo '<div class="vwp-button-container" style="margin-top:10px;">';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
		echo '<input type="hidden" name="action" value="vontmnt_delete_log">';
		wp_nonce_field( 'delete_log_action' );
		echo '<button type="submit" class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Delete Log', 'v-wp-dashboard' ) . '</button>';
		echo '</form>';
		echo '</div>';
		echo '</div>';
}

/**
 * Helper function to get the last N lines of a file efficiently.
 *
 * @param string $filepath Path to the file.
 * @param int    $lines    Number of lines to retrieve. Default 25.
 * @return array           Array of lines from the end of the file.
 */
function vwplogs_tail( string $filepath, int $lines = 25 ): array {
	if ( ! is_readable( $filepath ) ) {
		return array();
	}

	if ( $lines <= 0 ) {
		return file( $filepath, FILE_IGNORE_NEW_LINES );
	}

	try {
		$file = new SplFileObject( $filepath, 'r' );
		$file->seek( PHP_INT_MAX );
		$last_line = $file->key();
		$output    = array();

		for ( $i = 0; $i < $lines && $last_line - $i >= 0; $i++ ) {
			$file->seek( $last_line - $i );
			$output[] = rtrim( $file->current(), "\r\n" );
		}

		return array_reverse( $output );
	} catch ( Exception $e ) {
		return array();
	}
}
