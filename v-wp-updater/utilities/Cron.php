<?php
/**
 * Cron Helper Class
 *
 * Handles custom WordPress cron schedules.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cron
 *
 * Provides custom cron schedule intervals.
 *
 * @since 2.0.0
 */
class Cron {

	/**
	 * Register custom cron schedules.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function register_schedules(): void {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_weekly_schedule' ) );
	}

	/**
	 * Add a weekly cron schedule.
	 *
	 * Adds a 'weekly' interval (7 days) to WordPress cron schedules.
	 * Ensures weekly events (like debug log deletion) can be scheduled.
	 *
	 * @since 2.0.0
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules including 'weekly'.
	 */
	public static function add_weekly_schedule( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'v-wp-dashboard' ),
			);
		}
		return $schedules;
	}
}
