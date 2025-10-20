<?php
/**
 * Settings Widget
 *
 * @package V_WP_Updater
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the settings widget for managing plugin options.
 *
 * Only visible to the user with username 'vontainment'.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_widget_settings_display(): void {
	// Check current user capability.
	if ( ! current_user_can( 'manage_options' ) ) {
		echo '<p>' . esc_html__( 'You do not have permission to access this settings widget.', 'v-wp-updater' ) . '</p>';
		return;
	}

	// Handle form submission: verify nonce and capability.
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['vontmnt_settings_nonce'] ) ) {
		// check_admin_referer will verify the nonce and die on failure.
		check_admin_referer( 'vontmnt_save_settings', 'vontmnt_settings_nonce' );
		vontmnt_save_settings();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'v-wp-updater' ) . '</p></div>';
	}

	// Only the updates section is exposed.
	$updates = array(
		'title'  => __( 'Update Settings', 'v-wp-updater' ),
		'fields' => array(
			'update_plugins'    => array( 'label' => __( 'Enable Plugin Updates', 'v-wp-updater' ), 'type' => 'select' ),
			'update_themes'     => array( 'label' => __( 'Enable Theme Updates', 'v-wp-updater' ), 'type' => 'select' ),
			'update_key'        => array( 'label' => __( 'Update Key', 'v-wp-updater' ), 'type' => 'text' ),
			'update_plugin_url' => array( 'label' => __( 'Plugin Update URL', 'v-wp-updater' ), 'type' => 'text', 'default' => 'https://wp-updates.servicesbyv.com/plugins/api.php' ),
			'update_theme_url'  => array( 'label' => __( 'Theme Update URL', 'v-wp-updater' ), 'type' => 'text', 'default' => 'https://wp-updates.servicesbyv.com/themes/api.php' ),
		),
	);

	echo '<div class="vwp-widget vwp-widget-settings">';
	echo '<form method="post" action="">';
	wp_nonce_field( 'vontmnt_save_settings', 'vontmnt_settings_nonce' );

	echo '<h3>' . esc_html( $updates['title'] ) . '</h3>';
	echo '<table class="form-table">';

	foreach ( $updates['fields'] as $field_key => $field ) {
		$option_name = 'vontmnt_' . $field_key;
		$value       = vontmnt_get_option( $field_key, $field['default'] ?? '' );

		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( $option_name ) . '">' . esc_html( $field['label'] ) . '</label></th>';
		echo '<td>';

		if ( 'select' === $field['type'] ) {
			echo '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $option_name ) . '">';
			echo '<option value="false"' . selected( $value, 'false', false ) . '>' . esc_html__( 'False', 'v-wp-updater' ) . '</option>';
			echo '<option value="true"' . selected( $value, 'true', false ) . '>' . esc_html__( 'True', 'v-wp-updater' ) . '</option>';
			echo '</select>';
		} else {
			echo '<input type="text" name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $option_name ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
		}

		echo '</td>';
		echo '</tr>';
	}

	echo '</table>';
	echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="' . esc_attr__( 'Save Settings', 'v-wp-updater' ) . '"></p>';
	echo '</form>';
	echo '</div>';

}

/**
 * Save settings from the settings form.
 *
 * @since 2.0.0
 * @return void
 */
function vontmnt_save_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Only save the update-related options.
	$keys = array(
		'update_plugins',
		'update_themes',
		'update_key',
		'update_plugin_url',
		'update_theme_url',
	);

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce already verified in display handler.
	foreach ( $keys as $key ) {
		$option_name = 'vontmnt_' . $key;
		if ( isset( $_POST[ $option_name ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) );
			update_option( $option_name, $value, false );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
