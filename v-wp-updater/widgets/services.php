<?php
/**
 * Services Widget
 *
 * @package V_WP_Dashboard
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use VWPDashboard\Helpers\Options;

/**
 * Outputs the services widget with Vontainment services and tools.
 *
 * Displays a grid of buttons linking to various Vontainment services and tools,
 * followed by server control buttons, the latest news widget, and contact information.
 *
 * @since 1.0.0
 *
 * @return void
 */
function vontmnt_widget_services_display(): void {
	echo '<div class="vwp-widget vwp-widget-services">';
	echo '<div class="vwp-services-grid">';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://vontainment.com/' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Vontainment', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://vontainment.com/tools/qr-generator/' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'QR Codes', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://vontainment.com/tools/seo-audit/' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'SEO Audit', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://analytics.servicesbyv.com/login' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Analytics', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://ai-statuses.servicesbyv.com/' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'AI Statuses', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://vonsrc.com/' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Link Shortener', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://status.servicesbyv.com/' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Status Systems', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://crm.vontainment.com/' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'CRM Access', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '<div class="vwp-button-container"><a href="' . esc_url( 'https://chat.openai.com/auth/login' ) . '"><button class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Chat GPT', 'v-wp-dashboard' ) . '</button></a></div>';
	echo '</div>';

	vontmnt_server_control_widget();

	// Display status message from transient instead of URL parameters.
	$status_message = get_transient( 'vontmnt_widget_status_message' );
	if ( $status_message ) {
		echo '<div class="vwp-status-message">' . esc_html( $status_message ) . '</div>';
		delete_transient( 'vontmnt_widget_status_message' );
	}

	vontmnt_latest_news_widget();

	echo '<div class="vwp-contact-info">';
	echo '<h2>' . esc_html__( 'Contact Vontainment', 'v-wp-dashboard' ) . '</h2>';
	echo '<p>' . esc_html__( 'Phone: 941-313-2091', 'v-wp-dashboard' ) . '</p>';
	echo '<p>' . esc_html__( 'Email: ', 'v-wp-dashboard' ) . '<a href="' . esc_url( 'mailto:contact@vontainment.com' ) . '">' . esc_html__( 'contact@vontainment.com', 'v-wp-dashboard' ) . '</a></p>';
	echo '</div>'; // Close .vwp-contact-info.
	echo '</div>'; // Close .vwp-widget-services.
}

/**
 * Outputs server control buttons.
 *
 * Displays buttons for purging caches, updating plugins, and updating themes,
 * based on defined constants.
 *
 * @since 1.0.0
 *
 * @return void
 */
function vontmnt_server_control_widget(): void {
	echo '<div class="vwp-server-control-grid vwp-button-container">';

	if (
						Options::is_true( 'clear_caches_hestia' ) ||
						Options::is_true( 'clear_caches_cloudflare' ) ||
						Options::is_true( 'clear_caches_opcache' )
				) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
		echo '<input type="hidden" name="action" value="vontmnt_clear_caches">';
		wp_nonce_field( 'clear_caches_action' );
		echo '<button type="submit" class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Purge Caches', 'v-wp-dashboard' ) . '</button>';
		echo '</form>';
	}

	// @intelephense-ignore
	if ( Options::is_true( 'update_plugins' ) ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
		echo '<input type="hidden" name="action" value="vontmnt_update_plugins">';
		wp_nonce_field( 'update_plugins_action' );
		echo '<button type="submit" class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Update Plugins', 'v-wp-dashboard' ) . '</button>';
		echo '</form>';
	}

	// @intelephense-ignore
	if ( Options::is_true( 'update_themes' ) ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
		echo '<input type="hidden" name="action" value="vontmnt_update_themes">';
		wp_nonce_field( 'update_themes_action' );
		echo '<button type="submit" class="vwp-button button button-primary custom-shadow">' . esc_html__( 'Update Themes', 'v-wp-dashboard' ) . '</button>';
		echo '</form>';
	}
	echo '</div>';
}

/**
 * Outputs the latest news widget.
 *
 * Fetches and displays the latest news items from the Vontainment RSS feed.
 *
 * @since 1.0.0
 *
 * @return void
 */
function vontmnt_latest_news_widget(): void {
	$cache_key  = 'vontmnt_latest_news_rss_cache';
	$cache_time = DAY_IN_SECONDS; // 24 hours

	$cached    = get_option( $cache_key, false );
	$rss_items = array();
	$maxitems  = 0;

	if ( $cached && isset( $cached['timestamp'], $cached['items'], $cached['maxitems'] ) && ( time() - $cached['timestamp'] < $cache_time ) ) {
		$rss_items = $cached['items'];
		$maxitems  = $cached['maxitems'];
        } else {
                require_once ABSPATH . WPINC . '/feed.php';
                $rss = fetch_feed( 'https://vontainment.com/feed/' );
                if ( ! is_wp_error( $rss ) ) {
			$maxitems = $rss->get_item_quantity( 5 );
			$items    = $rss->get_items( 0, $maxitems );
			// Serialize only the data needed for display (title, permalink, date, description).
			$rss_items = array();
			foreach ( $items as $item ) {
				$rss_items[] = array(
					'title'       => $item->get_title(),
					'permalink'   => $item->get_permalink(),
					'date'        => $item->get_date( 'j F Y | g:i a' ),
					'description' => wp_strip_all_tags( $item->get_description() ),
				);
			}
			update_option(
				$cache_key,
				array(
					'timestamp' => time(),
					'items'     => $rss_items,
					'maxitems'  => $maxitems,
				),
				false
			);
		}
	}

	if ( $maxitems > 0 && ! empty( $rss_items ) ) {
		echo '<div class="vontainment-news-feed" id="vontmnt-news-feed">';
		echo '<h2>' . esc_html__( 'Vontainment\'s Latest', 'v-wp-dashboard' ) . '</h2>';

		$first_item = $rss_items[0];
		echo '<div class="news-item" id="vontmnt-news-item-main">';
		echo '<a href="' . esc_url( $first_item['permalink'] ) . '" title="' . esc_attr( $first_item['title'] ) . '"><strong>' . esc_html( $first_item['title'] ) . '</strong></a>';
		echo '<p>' . esc_html__( 'Posted on: ', 'v-wp-dashboard' ) . esc_html( $first_item['date'] ) . '</p>';
		echo '<p>' . esc_html( $first_item['description'] ) . '</p>';
		echo '</div>';

		echo '<ul id="vontmnt-news-list">';
		for ( $i = 1; $i < $maxitems; $i++ ) {
			if ( ! isset( $rss_items[ $i ] ) ) {
				continue;
			}
			$item = $rss_items[ $i ];
			echo '<li>';
			echo '<a href="' . esc_url( $item['permalink'] ) . '" title="' . esc_attr( $item['title'] ) . '">';
			echo esc_html( $item['title'] );
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';
	} else {
		echo '<p id="vontmnt-news-none">' . esc_html__( 'No items found or unable to fetch the RSS feed.', 'v-wp-dashboard' ) . '</p>';
	}
}
