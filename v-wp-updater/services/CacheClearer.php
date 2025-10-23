<?php
/**
 * Clear Caches
 *
 * Provides functionality to clear various caches.
 *
 * @package V_WP_Dashboard
 * @since 1.0.0
 */

namespace VWPDashboard\Services;

use VWPDashboard\Helpers\Options;

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

/**
 * Class CacheClearer
 *
 * Handles clearing various caches including Hestia, Cloudflare, and OPcache.
 *
 * @since 1.0.0
 */
class CacheClearer {

		/**
		 * Singleton instance of the cache clearer.
		 *
		 * @since 2.0.0
		 *
		 * @var self|null
		 */
	private static ?self $instance = null;

	/**
	 * Check whether shell execution is available.
	 *
	 * Verifies `function_exists('exec')` and that it isn't disabled via
	 * the `disable_functions` ini directive.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function exec_available(): bool {
		// Basic availability check.
		if ( ! function_exists( 'exec' ) ) {
			return false;
		}

		// Ensure exec is not disabled via php.ini.
		$disabled = ini_get( 'disable_functions' );
		if ( is_string( $disabled ) && '' !== trim( $disabled ) ) {
			$disabled_list = array_map( 'trim', explode( ',', $disabled ) );
			if ( in_array( 'exec', $disabled_list, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Initialize the cache clearing functionality.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
			$this->init_hooks();
	}

		/**
		 * Retrieve the singleton instance.
		 *
		 * Lazily creates the instance so hooks are only registered once per
		 * request when needed.
		 *
		 * @since 2.0.0
		 *
		 * @return self
		 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
				self::$instance = new self();
		}

			return self::$instance;
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'save_post', array( $this, 'flush_caches' ) );
		add_action( 'wp_trash_post', array( $this, 'flush_caches' ) );
		add_action( 'clean_post_cache', array( $this, 'flush_caches' ) );
	}

		/**
		 * Flushes various caches based on defined constants.
		 *
		 * Clears caches for Hestia, Cloudflare, and OPcache if the respective
		 * constants are defined and set to true.
		 *
                * @since 1.0.0
                * @param int|null     $post_id Optional post ID provided by WordPress hooks.
                * @param \WP_Post|null $post    Optional post object provided by WordPress hooks.
                * @param bool|null    $update  Optional update flag provided by WordPress hooks.
                * @return bool True when all enabled cache clears succeed, false otherwise.
                */
        public function flush_caches( $post_id = null, $post = null, $update = null ): bool {
                        $success          = true;
                        $status_messages = array();

                if ( $this->should_clear_hestia_cache() ) {
                                $success = $this->clear_hestia_cache() && $success;
                }

                if ( $this->should_clear_cloudflare_cache() ) {
                        $success = $this->clear_cloudflare_cache() && $success;
                }

                if ( $this->should_clear_opcache() ) {
                        $success = $this->clear_opcache() && $success;
                }

                $external_object_cache = false;
                if ( function_exists( 'wp_using_ext_object_cache' ) ) {
                        $external_object_cache = wp_using_ext_object_cache();
                } elseif ( function_exists( 'wp_cache_flush' ) ) {
                        $external_object_cache = true;
                }

                if ( $external_object_cache && function_exists( 'wp_cache_flush' ) ) {
                        $object_cache_success = wp_cache_flush();
                        $success              = $object_cache_success && $success;

                        if ( ! $object_cache_success ) {
                                $status_messages[] = __( 'Object cache flush failed.', 'v-wp-dashboard' );
                        }
                }

                if ( $success ) {
                        $message = __( '✅ All caches cleared successfully!', 'v-wp-dashboard' );
                } else {
                        $message = __( '❌ Sorry, there was an error clearing caches.', 'v-wp-dashboard' );
                        if ( ! empty( $status_messages ) ) {
                                $message .= ' ' . implode( ' ', $status_messages );
                        }
                }

                        set_transient( 'vontmnt_widget_status_message', $message, 30 );

                        return $success;
        }

	/**
	 * Check if Hestia cache clearing is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function should_clear_hestia_cache(): bool {
			return Options::is_true( 'clear_caches_hestia' )
					&& ! Options::is_true( 'disable_shell_cache_clearing' );
	}

	/**
	 * Check if Cloudflare cache clearing is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function should_clear_cloudflare_cache(): bool {
		return Options::is_true( 'clear_caches_cloudflare' );
	}

	/**
	 * Check if OPcache clearing is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function should_clear_opcache(): bool {
		return Options::is_true( 'clear_caches_opcache' );
	}

	/**
	 * Clear Hestia cache.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	private function clear_hestia_cache(): bool {
		if ( Options::is_true( 'disable_shell_cache_clearing' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Shell-based cache clearing is disabled (disable_shell_cache_clearing option).' );
			}
			return false;
		}

		if ( ! $this->exec_available() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'exec() is not available or disabled via php.ini.' );
			}
			return false;
		}

		// Determine the system user to correctly target Hestia's cache for the current site.
		// This is necessary for `v-purge-nginx-cache` which requires the system user.
		$user_output = array();
		$return_var  = 0;
		exec( 'whoami', $user_output, $return_var ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Required for Hestia integration.
		if ( 0 !== $return_var || empty( $user_output ) || empty( $user_output[0] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Failed to determine system user for Hestia cache purge. Exit code: %d, Output: %s', (int) $return_var, wp_json_encode( $user_output ) ) );
			}
			return false;
		}

			$user    = trim( $user_output[0] );
			$domain  = rawurlencode( wp_parse_url( site_url(), PHP_URL_HOST ) );
			$command = sprintf(
				'sudo /usr/local/hestia/bin/v-purge-nginx-cache %s %s',
				escapeshellarg( $user ),
				escapeshellarg( $domain )
			);

		// Execute Hestia CP command to purge Nginx cache.
		// Assumes appropriate sudo permissions are configured for the web server user to run this specific command.
		$output     = array();
		$return_var = 0;
		exec( $command, $output, $return_var ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Required for Hestia integration.

		if ( 0 !== $return_var ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Hestia cache purge command failed. Exit code: %d, Command: %s, Output: %s', (int) $return_var, $command, wp_json_encode( $output ) ) );
			}
			return false;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Hestia cache purged successfully.' );
		}

			return true;
	}

	/**
	 * Clear Cloudflare cache.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	private function clear_cloudflare_cache(): bool {
		$cloudflare_api_key = Options::get( 'cloudflare_api_key' );
		$cloudflare_zone_id = Options::get( 'cloudflare_zone_id' );

		if ( empty( $cloudflare_api_key ) || empty( $cloudflare_zone_id ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Cloudflare API key or zone ID not set.' );
			}
			return false;
		}

		$cloudflare_email = Options::get( 'cloudflare_email' );

		if ( empty( $cloudflare_email ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Cloudflare email not set.' );
			}
			return false;
		}

		$args = array(
			'headers'     => array(
				'X-Auth-Email' => $cloudflare_email,
				'X-Auth-Key'   => $cloudflare_api_key,
				'Content-Type' => 'application/json',
			),
			'body'        => wp_json_encode( array( 'purge_everything' => true ) ),
			'method'      => 'POST',
			'sslverify'   => true,
			'timeout'     => 20,
			'redirection' => 3,
		);

		$response = wp_remote_post( "https://api.cloudflare.com/client/v4/zones/$cloudflare_zone_id/purge_cache", $args );

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Cloudflare API request failed: ' . $response->get_error_message() );
			}
			return false;
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Cloudflare purge failed. HTTP %d. Body: %s', (int) $status_code, (string) $response_body ) );
			}
			return false;
		}

		if ( null === $response_data ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Failed to parse Cloudflare API response JSON.' );
			}
			return false;
		}

		if ( ! isset( $response_data['success'] ) || ! $response_data['success'] ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Failed to purge Cloudflare cache: ' . $response_body );
			}
			return false;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Cloudflare cache purged successfully.' );
		}

		return true;
	}

	/**
	 * Clear OPcache.
	 *
	 * @since 1.0.0
	 * @return bool Success status.
	 */
	private function clear_opcache(): bool {
		if ( ! function_exists( '\opcache_reset' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'OPcache is not enabled.' );
			}
			return false;
		}

		if ( ! \opcache_reset() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Failed to clear OPcache.' );
			}
			return false;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'OPcache cleared successfully.' );
		}

		return true;
	}
}
