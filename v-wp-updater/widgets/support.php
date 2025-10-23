<?php
/**
 * Support Widget
 *
 * @package V_WP_Dashboard
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs the support widget with an iframe.
 *
 * Displays an iframe that loads the support ticket form from the Vontainment CRM.
 *
 * @since 1.0.0
 *
 * @return void
 */
function vontmnt_widget_support_display(): void {
	echo '<div class="vwp-widget vwp-widget-support">';
	echo '<iframe class="vwp-support-iframe" src="' . esc_url( 'https://crm.vontainment.com/forms/ticket' ) . '" frameborder="0" allowfullscreen></iframe>';
	echo '</div>';
}
