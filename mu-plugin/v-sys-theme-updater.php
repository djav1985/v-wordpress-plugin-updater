<?php

/**
 * Theme Name: WP Theme Updater
 * Theme URI: https://vontainment.com
 * Description: This theme updates your WordPress themes.
 * Version: 1.2.0
 * Author: Vontainment
 * Author URI: https://vontainment.com
 *
 * @package VontainmentThemeUpdater
 */

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Schedule daily theme update checks.
 */
function vontmnt_theme_updater_schedule_updates()
{
    if (! wp_next_scheduled('vontmnt_theme_updater_check_updates')) {
        wp_schedule_event(time(), 'daily', 'vontmnt_theme_updater_check_updates');
    }
}

add_action('wp', 'vontmnt_theme_updater_schedule_updates');

add_action('vontmnt_theme_updater_check_updates', 'vontmnt_theme_updater_run_updates');

/**
 * Run theme updates for all installed themes.
 */
function vontmnt_theme_updater_run_updates()
{
    $themes = wp_get_themes();
    foreach ($themes as $theme) {
        $theme_slug        = $theme->get_stylesheet();
        $installed_version = $theme->get('Version');
        $api_url = add_query_arg(
            array(
                'type'    => 'theme',
                'domain'  => rawurlencode(wp_parse_url(site_url(), PHP_URL_HOST)),
                'slug'    => rawurlencode($theme_slug),
                'version' => rawurlencode($installed_version),
                'key'     => VONTMENT_KEY,
            ),
            VONTMENT_THEMES
        );

        $response      = wp_remote_get($api_url, array( 'sslverify' => false ));
        $http_code     = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($http_code === 200 && ! empty($response_body)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload_dir      = wp_upload_dir();
            $theme_zip_file = $upload_dir['path'] . '/' . basename($theme_slug) . '.zip';
            file_put_contents($theme_zip_file, $response_body);

            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }

            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            $upgrader = new Theme_Upgrader();
            add_filter(
                'upgrader_package_options',
                function ($options) use ($theme_zip_file) {
                    $options['package']           = $theme_zip_file;
                    $options['clear_destination'] = true;
                    return $options;
                }
            );
            $upgrader->install($theme_zip_file);
            remove_all_filters('upgrader_package_options');

            // Delete the theme zip file using wp_delete_file.
            wp_delete_file($theme_zip_file);
        } elseif ($http_code === 204) {
            // No updates, check next theme.
            continue;
        } else {
            // For 400, 403, or any other unexpected code, stop further processing.
            break;
        }
    }
}
