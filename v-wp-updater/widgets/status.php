<?php
/**
 * Status Widget
 *
 * @package V_WP_Dashboard
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use VWPDashboard\Helpers\Options;

/**
 * Returns a friendly name for a given cron job.
 *
 * @since 1.0.0
 *
 * @param string $job The cron job name.
 * @return string The friendly name.
 */
function vontmnt_get_friendly_cron_job_name( string $job ): string {
		$friendly_names = array(
			'vontmnt_plugin_updater_check_updates' => __( 'Plugin Updater Check', 'v-wp-dashboard' ),
			'vontmnt_theme_updater_check_updates'  => __( 'Theme Updater Check', 'v-wp-dashboard' ),
			'delete_debug_log_weekly_event'        => __( 'Delete Debug Log Weekly', 'v-wp-dashboard' ),
		);

		return $friendly_names[ $job ] ?? $job;
}

/**
 * Outputs the status of various Vontainment services.
 *
 * Displays the status of plugin updates, theme updates, cache optimization,
 * added security, added optimization, and cron jobs.
 *
 * @since 1.0.0
 *
 * @return void
 */
function vontmnt_widget_status_display(): void {
	$plugin_updates     = get_option( 'vontmnt-plup', false );
	$theme_updates      = get_option( 'vontmnt-thup', false );
	$litespeed_active   = is_plugin_active( 'litespeed-cache/litespeed-cache.php' );
	$cache_optimization = Options::is_true( 'clear_caches_hestia' ) ||
		Options::is_true( 'clear_caches_cloudflare' ) ||
		Options::is_true( 'clear_caches_opcache' ) ||
		$litespeed_active;
	$added_security     = is_plugin_active( 'wordfence/wordfence.php' );
	$added_optimization = is_plugin_active( 'perfmatters/perfmatters.php' ) || $litespeed_active;
	$cron_jobs          = array();
	if ( Options::is_true( 'update_plugins' ) ) {
		$cron_jobs['vontmnt_plugin_updater_check_updates'] = wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' );
	}
	if ( Options::is_true( 'update_themes' ) ) {
		$cron_jobs['vontmnt_theme_updater_check_updates'] = wp_next_scheduled( 'vontmnt_theme_updater_check_updates' );
	}
	$cron_jobs['delete_debug_log_weekly_event'] = wp_next_scheduled( 'delete_debug_log_weekly_event' );

	echo '<div class="vwp-widget vwp-widget-status">';
	if ( $plugin_updates ) {
		echo '<p class="vwp-status status success">✅ <strong>' . esc_html__( 'Vontainment Premium Plugin Updates:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'You are being protected with premium updates.', 'v-wp-dashboard' ) . '</p>';
	} else {
		echo '<p class="vwp-status status error">❌ <strong>' . esc_html__( 'Vontainment Premium Plugin Updates:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'You are not currently receiving premium updates.', 'v-wp-dashboard' ) . '</p>';
	}

	if ( Options::is_true( 'update_themes' ) ) {
		if ( $theme_updates ) {
			echo '<p class="vwp-status status success">✅ <strong>' . esc_html__( 'Vontainment Premium Theme Updates:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'Your themes are secured and up to date.', 'v-wp-dashboard' ) . '</p>';
		} else {
			echo '<p class="vwp-status status error">❌ <strong>' . esc_html__( 'Vontainment Premium Theme Updates:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'Your themes are not currently receiving premium updates.', 'v-wp-dashboard' ) . '</p>';
		}
	}

	if ( $cache_optimization ) {
		echo '<p class="vwp-status status optimization">⚡ <strong>' . esc_html__( 'Optimization Status:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'You are receiving Vontainment Premium Optimizations.', 'v-wp-dashboard' ) . '</p>';
	} else {
		echo '<p class="vwp-status status warning">⚠️ <strong>' . esc_html__( 'Optimization Status:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'You are receiving standard optimizations.', 'v-wp-dashboard' ) . '</p>';
	}

	if ( $added_security ) {
		echo '<p class="vwp-status status security">🛡️ <strong>' . esc_html__( 'Added Security:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'Wordfence is installed, enhancing security.', 'v-wp-dashboard' ) . '</p>';
	}

	if ( $added_optimization ) {
		echo '<p class="vwp-status status optimization">🚀 <strong>' . esc_html__( 'Added Optimization:', 'v-wp-dashboard' ) . '</strong> ' . esc_html__( 'Cache and optimization tools installed, enhancing performance.', 'v-wp-dashboard' ) . '</p>';
	}

	echo '<h3>' . esc_html__( 'Cron Jobs Status:', 'v-wp-dashboard' ) . '</h3>';
	foreach ( $cron_jobs as $job => $timestamp ) {
		$friendly_name = vontmnt_get_friendly_cron_job_name( $job );
		if ( $timestamp ) {
			echo '<p class="vwp-status status success">✅ <strong>' . esc_html( $friendly_name ) . ':</strong> ' . esc_html__( 'Scheduled', 'v-wp-dashboard' ) . '</p>';
		} else {
			echo '<p class="vwp-status status error">❌ <strong>' . esc_html( $friendly_name ) . ':</strong> ' . esc_html__( 'Not Scheduled', 'v-wp-dashboard' ) . '</p>';
		}
	}

	echo '</div>';
}
