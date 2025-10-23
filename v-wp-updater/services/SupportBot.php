<?php
/**
 * Support Bot Integration Class
 *
 * Handles the integration of a support chatbot in the WordPress admin area.
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

namespace VWPDashboard\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SupportBot
 *
 * Integrates Dify chatbot into WordPress admin.
 *
 * @since 2.0.0
 */
class SupportBot {

	/**
	 * Initialize the support bot.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function init(): void {
		static $initialized = false;

		if ( $initialized ) {
			return;
		}

		$initialized = true;

		add_action( 'admin_footer', array( __CLASS__, 'add_chatbot_scripts' ) );
	}

	/**
	 * Adds the chatbot configuration and scripts before the closing body tag.
	 *
	 * This function outputs the necessary JavaScript configuration and script tags
	 * for the Dify chatbot integration in the WordPress admin area.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function add_chatbot_scripts(): void {
		?>
		<script>
		window.difyChatbotConfig = {
			token: '2g9RUBFzvFwJfMr1',
			baseUrl: 'https://dify.hugev.xyz',
			systemVariables: {
				// user_id: 'YOU CAN DEFINE USER ID HERE',
				// conversation_id: 'YOU CAN DEFINE CONVERSATION ID HERE, IT MUST BE A VALID UUID',
			},
			userVariables: {
				// avatar_url: 'YOU CAN DEFINE USER AVATAR URL HERE',
				// name: 'YOU CAN DEFINE USER NAME HERE',
			},
		}
		</script>
		<script
			src="https://dify.hugev.xyz/embed.min.js"
			id="2g9RUBFzvFwJfMr1"
			defer>
		</script>
		<style>
			#dify-chatbot-bubble-button {
				background-color: #66cc33 !important;
			}
			#dify-chatbot-bubble-window {
				width: 24rem !important;
				height: 40rem !important;
				border-radius: 16px;
				position: fixed;
			}
		</style>
		<?php
	}
}
