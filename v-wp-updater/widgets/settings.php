<?php
/**
 * Settings Widget
 *
 * @package VWPU
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use VWPU\Helpers\Options;

/**
 * Display the settings widget for managing plugin options.
 *
 * @since 2.0.0
 * @return void
 */
function vwpu_widget_settings_display(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to access this settings widget.', 'v-wp-updater' ) . '</p>';
			return;
	}

		$masked_fields = array(
			'update_key',
		);

		// Handle form submission.
		if ( isset( $_POST['vwpu_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vwpu_settings_nonce'] ) ), 'vwpu_save_settings' ) ) {
			vwpu_save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'v-wp-updater' ) . '</p></div>';
		}

		// Define settings structure.
		$settings = array(
			'updates'  => array(
				'title'  => __( 'Update Settings', 'v-wp-updater' ),
				'fields' => array(
					'update_plugins'    => array(
						'label' => __( 'Enable Plugin Updates', 'v-wp-updater' ),
						'type'  => 'select',
					),
					'update_themes'     => array(
						'label' => __( 'Enable Theme Updates', 'v-wp-updater' ),
						'type'  => 'select',
					),
					'update_key'        => array(
						'label' => __( 'Update Key', 'v-wp-updater' ),
						'type'  => 'text',
					),
					'update_plugin_url' => array(
						'label'   => __( 'Plugin Update URL', 'v-wp-updater' ),
						'type'    => 'text',
						'default' => 'https://wp-updates.servicesbyv.com/plugins/api.php',
					),
					'update_theme_url'  => array(
						'label'   => __( 'Theme Update URL', 'v-wp-updater' ),
						'type'    => 'text',
						'default' => 'https://wp-updates.servicesbyv.com/themes/api.php',
					),
				),
			),
		);

		// Display settings form.
		echo '<form method="post" action="">';
		wp_nonce_field( 'vwpu_save_settings', 'vwpu_settings_nonce' );

		foreach ( $settings as $section_key => $section ) {
			echo '<h3>' . esc_html( $section['title'] ) . '</h3>';
			echo '<table class="form-table">';

			foreach ( $section['fields'] as $field_key => $field ) {
				$value   = Options::get( $field_key, $field['default'] ?? '' );
				$is_masked = in_array( $field_key, $masked_fields, true );

				// For masked fields, check if there's already a value and display placeholder.
				if ( $is_masked && ! empty( $value ) ) {
					$display_value = '********';
				} else {
					$display_value = $value;
				}

				echo '<tr>';
				echo '<th scope="row"><label for="' . esc_attr( $field_key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
				echo '<td>';

				switch ( $field['type'] ) {
					case 'select':
						echo '<select name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '">';
						echo '<option value="false"' . selected( $value, 'false', false ) . '>' . esc_html__( 'Disabled', 'v-wp-updater' ) . '</option>';
						echo '<option value="true"' . selected( $value, 'true', false ) . '>' . esc_html__( 'Enabled', 'v-wp-updater' ) . '</option>';
						echo '</select>';
						break;

					case 'text':
						echo '<input type="text" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" value="' . esc_attr( $display_value ) . '" class="regular-text"';
						if ( $is_masked && ! empty( $value ) ) {
							echo ' placeholder="' . esc_attr__( 'Leave blank to keep current value', 'v-wp-updater' ) . '"';
						}
						echo ' />';
						break;
				}

				echo '</td>';
				echo '</tr>';
			}

			echo '</table>';
		}

		submit_button( __( 'Save Settings', 'v-wp-updater' ) );
		echo '</form>';
}

/**
 * Save widget settings.
 *
 * @since 2.0.0
 * @return void
 */
function vwpu_save_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$field_keys = array(
		'update_plugins',
		'update_themes',
		'update_key',
		'update_plugin_url',
		'update_theme_url',
	);

	foreach ( $field_keys as $field_key ) {
		if ( isset( $_POST[ $field_key ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) );

			// For masked fields, only update if a new value was provided.
			if ( in_array( $field_key, array( 'update_key' ), true ) ) {
				if ( ! empty( $value ) && $value !== '********' ) {
					Options::set( $field_key, $value );
				}
			} else {
				Options::set( $field_key, $value );
			}
		}
	}
}
