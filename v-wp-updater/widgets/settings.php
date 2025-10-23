<?php
/**
 * Settings Widget
 *
 * @package V_WP_Dashboard
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use VWPDashboard\Helpers\Options;
use VWPDashboard\Helpers\Security;

/**
 * Display the settings widget for managing plugin options.
 *
 * Only visible to the user with username 'vontainment'.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_widget_settings_display(): void {
	if ( ! Security::is_vontainment_user() || ! Security::can_manage_options() ) {
			echo '<p>' . esc_html__( 'You do not have permission to access this settings widget.', 'v-wp-dashboard' ) . '</p>';
			return;
	}

		$masked_fields = array(
			'cloudflare_api_key',
			'update_key',
			'ssh_password',
			'pushover_token',
		);

		// Handle form submission.
		if ( isset( $_POST['vontmnt_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vontmnt_settings_nonce'] ) ), 'vontmnt_save_settings' ) ) {
			vontmnt_save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'v-wp-dashboard' ) . '</p></div>';
		}

		// Define settings structure.
		$settings = array(
			'cache'    => array(
				'title'  => __( 'Cache Settings', 'v-wp-dashboard' ),
				'fields' => array(
					'clear_caches_hestia'     => array(
						'label' => __( 'Clear Hestia Cache', 'v-wp-dashboard' ),
						'type'  => 'select',
					),
					'clear_caches_cloudflare' => array(
						'label' => __( 'Clear Cloudflare Cache', 'v-wp-dashboard' ),
						'type'  => 'select',
					),
					'cloudflare_email'        => array(
						'label' => __( 'Cloudflare Email', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'cloudflare_zone_id'      => array(
						'label' => __( 'Cloudflare Zone ID', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'cloudflare_api_key'      => array(
						'label' => __( 'Cloudflare API Key', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'clear_caches_opcache'    => array(
						'label' => __( 'Clear OPcache', 'v-wp-dashboard' ),
						'type'  => 'select',
					),
				),
			),
			'updates'  => array(
				'title'  => __( 'Update Settings', 'v-wp-dashboard' ),
				'fields' => array(
					'update_plugins'    => array(
						'label' => __( 'Enable Plugin Updates', 'v-wp-dashboard' ),
						'type'  => 'select',
					),
					'update_themes'     => array(
						'label' => __( 'Enable Theme Updates', 'v-wp-dashboard' ),
						'type'  => 'select',
					),
					'update_key'        => array(
						'label' => __( 'Update Key', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'update_plugin_url' => array(
						'label'   => __( 'Plugin Update URL', 'v-wp-dashboard' ),
						'type'    => 'text',
						'default' => 'https://wp-updates.servicesbyv.com/plugins/api.php',
					),
					'update_theme_url'  => array(
						'label'   => __( 'Theme Update URL', 'v-wp-dashboard' ),
						'type'    => 'text',
						'default' => 'https://wp-updates.servicesbyv.com/themes/api.php',
					),
				),
			),
			'backup'   => array(
				'title'  => __( 'Backup Settings', 'v-wp-dashboard' ),
				'fields' => array(
					'remote_backups'        => array(
						'label' => __( 'Enable Remote Backups', 'v-wp-dashboard' ),
						'type'  => 'select',
					),
					'remote_backups_timing' => array(
						'label' => __( 'Backup Timing', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'ssh_host'              => array(
						'label' => __( 'SSH Host', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'ssh_port'              => array(
						'label' => __( 'SSH Port', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'ssh_user'              => array(
						'label' => __( 'SSH User', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'ssh_password'          => array(
						'label' => __( 'SSH Password', 'v-wp-dashboard' ),
						'type'  => 'password',
					),
					'remote_backup_dir'     => array(
						'label' => __( 'Remote Backup Directory', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'max_backups'           => array(
						'label' => __( 'Maximum Backups', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
				),
			),
			'pushover' => array(
				'title'  => __( 'Pushover Notification Settings', 'v-wp-dashboard' ),
				'fields' => array(
					'pushover_token' => array(
						'label' => __( 'Pushover Token', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
					'pushover_user'  => array(
						'label' => __( 'Pushover User', 'v-wp-dashboard' ),
						'type'  => 'text',
					),
				),
			),
		);

		echo '<div class="vwp-widget vwp-widget-settings">';
		echo '<form method="post" action="">';
		wp_nonce_field( 'vontmnt_save_settings', 'vontmnt_settings_nonce' );

		foreach ( $settings as $section_key => $section ) {
			echo '<h3>' . esc_html( $section['title'] ) . '</h3>';
			echo '<table class="form-table">';

			foreach ( $section['fields'] as $field_key => $field ) {
						$option_name        = 'vontmnt_' . $field_key;
						$value              = Options::get( $field_key, $field['default'] ?? '' );
						$display_value      = $value;
						$sensitive_has_data = in_array( $field_key, $masked_fields, true ) && '' !== $value;
						$placeholder_text   = '';
						$autocomplete_value = '';

				if ( 'password' === $field['type'] ) {
						$autocomplete_value = 'new-password';
				}

				if ( $sensitive_has_data ) {
						$display_value      = '';
						$placeholder_text   = __( 'Value stored securely', 'v-wp-dashboard' );
						$autocomplete_value = ( 'password' === $field['type'] ) ? 'new-password' : 'off';
				}

						$attributes = array(
							'name'  => $option_name,
							'id'    => $option_name,
							'value' => $display_value,
							'class' => 'regular-text',
						);

						if ( '' !== $placeholder_text ) {
								$attributes['placeholder'] = $placeholder_text;
						}

						if ( '' !== $autocomplete_value ) {
								$attributes['autocomplete'] = $autocomplete_value;
						}

						// Determine CSS classes for conditional visibility.
						$row_classes = array();
						if ( in_array( $field_key, array( 'cloudflare_email', 'cloudflare_zone_id', 'cloudflare_api_key' ), true ) ) {
							$row_classes[] = 'vontmnt-cloudflare-field';
						}
						if ( 'update_plugin_url' === $field_key ) {
							$row_classes[] = 'vontmnt-plugin-update-url-field';
						}
						if ( 'update_theme_url' === $field_key ) {
							$row_classes[] = 'vontmnt-theme-update-url-field';
						}
						if ( 'update_key' === $field_key ) {
							$row_classes[] = 'vontmnt-update-key-field';
						}
						if ( in_array( $field_key, array( 'remote_backups_timing', 'ssh_host', 'ssh_port', 'ssh_user', 'ssh_password', 'remote_backup_dir', 'max_backups' ), true ) ) {
							$row_classes[] = 'vontmnt-backup-field';
						}

						$row_class_attr = ! empty( $row_classes ) ? ' class="' . esc_attr( implode( ' ', $row_classes ) ) . '"' : '';

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $row_class_attr is pre-escaped with esc_attr.
						echo '<tr' . $row_class_attr . '>';
						echo '<th scope="row"><label for="' . esc_attr( $option_name ) . '">' . esc_html( $field['label'] ) . '</label></th>';
						echo '<td>';

						if ( 'select' === $field['type'] ) {
								$data_trigger = '';
							if ( 'clear_caches_cloudflare' === $field_key ) {
								$data_trigger = ' data-toggle-target="vontmnt-cloudflare-field"';
							} elseif ( 'update_plugins' === $field_key ) {
									$data_trigger = ' data-toggle-target="vontmnt-plugin-update-url-field" data-toggle-shared="vontmnt-update-key-field"';
							} elseif ( 'update_themes' === $field_key ) {
								$data_trigger = ' data-toggle-target="vontmnt-theme-update-url-field" data-toggle-shared="vontmnt-update-key-field"';
							} elseif ( 'remote_backups' === $field_key ) {
								$data_trigger = ' data-toggle-target="vontmnt-backup-field"';
							}
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data_trigger contains hardcoded attribute names/values.
									echo '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $option_name ) . '" class="vontmnt-toggle-field"' . $data_trigger . '>';
									echo '<option value="false"' . selected( $value, 'false', false ) . '>' . esc_html__( 'False', 'v-wp-dashboard' ) . '</option>';
									echo '<option value="true"' . selected( $value, 'true', false ) . '>' . esc_html__( 'True', 'v-wp-dashboard' ) . '</option>';
									echo '</select>';
						} elseif ( 'password' === $field['type'] ) {
								echo '<input type="password"';
							foreach ( $attributes as $attribute_name => $attribute_value ) {
									printf( ' %s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
							}
								echo ' />';
						} else {
								echo '<input type="text"';
							foreach ( $attributes as $attribute_name => $attribute_value ) {
									printf( ' %s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
							}
								echo ' />';
						}

						if ( $sensitive_has_data ) {
							echo '<p class="description">' . esc_html__( 'A value is stored securely. Enter a new value to replace it.', 'v-wp-dashboard' ) . '</p>';
						}

						echo '</td>';
						echo '</tr>';
			}

			echo '</table>';
		}

		echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="' . esc_attr__( 'Save Settings', 'v-wp-dashboard' ) . '"></p>';
		echo '</form>';
		echo '</div>';

		// Add JavaScript for dynamic field visibility.
		?>
	<script type="text/javascript">
	(function() {
		'use strict';

		/**
		 * Toggle visibility of fields based on select value.
		 *
		 * @param {HTMLSelectElement} selectElement The select element that controls visibility.
		 */
		function toggleFieldVisibility(selectElement) {
			var value = selectElement.value;
			var targetClass = selectElement.getAttribute('data-toggle-target');
			var sharedClass = selectElement.getAttribute('data-toggle-shared');

			if (targetClass) {
				var targetRows = document.querySelectorAll('tr.' + targetClass);
				for (var i = 0; i < targetRows.length; i++) {
					if (value === 'true') {
						targetRows[i].style.display = '';
					} else {
						targetRows[i].style.display = 'none';
					}
				}
			}

			// Handle shared fields (like update_key) that depend on multiple toggles.
			if (sharedClass) {
				updateSharedFieldVisibility(sharedClass);
			}
		}

		/**
		 * Update visibility of shared fields based on all related toggles.
		 *
		 * @param {string} sharedClass The CSS class of the shared field.
		 */
		function updateSharedFieldVisibility(sharedClass) {
			var pluginUpdateToggle = document.getElementById('vontmnt_update_plugins');
			var themeUpdateToggle = document.getElementById('vontmnt_update_themes');
			var sharedRows = document.querySelectorAll('tr.' + sharedClass);

			if (!pluginUpdateToggle || !themeUpdateToggle || !sharedRows.length) {
				return;
			}

			// Show shared field only if at least one related toggle is true.
			var shouldShow = pluginUpdateToggle.value === 'true' || themeUpdateToggle.value === 'true';

			for (var i = 0; i < sharedRows.length; i++) {
				if (shouldShow) {
					sharedRows[i].style.display = '';
				} else {
					sharedRows[i].style.display = 'none';
				}
			}
		}

		// Initialize visibility on page load.
		document.addEventListener('DOMContentLoaded', function() {
			var toggleFields = document.querySelectorAll('.vontmnt-toggle-field');

			// Set initial visibility.
			for (var i = 0; i < toggleFields.length; i++) {
				toggleFieldVisibility(toggleFields[i]);
			}

			// Add change event listeners.
			for (var j = 0; j < toggleFields.length; j++) {
				toggleFields[j].addEventListener('change', function() {
					toggleFieldVisibility(this);
				});
			}
		});
	})();
	</script>
	<?php
}

/**
 * Save settings from the settings form.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_save_settings(): void {
	if ( ! Security::can_manage_options() || ! Security::is_vontainment_user() ) {
			return;
	}

		$option_keys = array(
			'clear_caches_hestia',
			'clear_caches_cloudflare',
			'cloudflare_email',
			'cloudflare_zone_id',
			'cloudflare_api_key',
			'clear_caches_opcache',
			'update_plugins',
			'update_themes',
			'update_key',
			'update_plugin_url',
			'update_theme_url',
			'remote_backups',
			'remote_backups_timing',
			'ssh_host',
			'ssh_port',
			'ssh_user',
			'ssh_password',
			'remote_backup_dir',
			'max_backups',
			'pushover_token',
			'pushover_user',
		);

		$sensitive_keys = array(
			'cloudflare_api_key',
			'update_key',
			'ssh_password',
			'pushover_token',
		);

        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified in vontmnt_widget_settings_display before calling this function and values are sanitized below.
		foreach ( $option_keys as $key ) {
				$option_name = 'vontmnt_' . $key;
			if ( isset( $_POST[ $option_name ] ) ) {
					$raw_value = wp_unslash( $_POST[ $option_name ] );

				if ( in_array( $key, $sensitive_keys, true ) && '' === $raw_value ) {
						continue;
				}

					$value = sanitize_text_field( $raw_value );
					update_option( $option_name, $value, false );
			}
		}

		// Ensure cron jobs are scheduled (they will check option values when running).
		if ( ! wp_next_scheduled( 'vontmnt_plugin_updater_check_updates' ) ) {
			wp_schedule_event( time(), 'daily', 'vontmnt_plugin_updater_check_updates' );
		}

		if ( ! wp_next_scheduled( 'vontmnt_theme_updater_check_updates' ) ) {
			wp_schedule_event( time(), 'daily', 'vontmnt_theme_updater_check_updates' );
		}

		// Check if backup timing changed and reschedule if needed.
		if ( isset( $_POST['vontmnt_remote_backups_timing'] ) ) {
			$timing = sanitize_text_field( wp_unslash( $_POST['vontmnt_remote_backups_timing'] ) );
			if ( empty( $timing ) ) {
				$timing = 'daily';
			}
			$schedules = wp_get_schedules();
			if ( ! isset( $schedules[ $timing ] ) ) {
				$timing = 'daily';
			}

			// Reschedule backup if timing changed.
			$current_schedule = wp_get_scheduled_event( 'vontmnt_create_backup' );
			if ( ! $current_schedule || $current_schedule->schedule !== $timing ) {
				wp_clear_scheduled_hook( 'vontmnt_create_backup' );
				wp_schedule_event( time(), $timing, 'vontmnt_create_backup' );
			}
		} elseif ( ! wp_next_scheduled( 'vontmnt_create_backup' ) ) {
			// Ensure backup job is scheduled if it doesn't exist.
			$timing = Options::get( 'remote_backups_timing', 'daily' );
			if ( empty( $timing ) ) {
				$timing = 'daily';
			}
			wp_schedule_event( time(), $timing, 'vontmnt_create_backup' );
		}
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}
