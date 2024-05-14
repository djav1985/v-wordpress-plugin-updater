<?php
/*
Theme Name: WP Theme Updater Multisite
Description: This theme updates your WordPress themes.
Author: Vontainment
Author URI: https://vontainment.com
Version: 2.0.0
*/

// Schedule the update check to run every day
add_action('wp', 'vontmnt_theme_updater_schedule_updates');

function vontmnt_theme_updater_schedule_updates()
{
    if (!wp_next_scheduled('vontmnt_theme_updater_check_updates')) {
        wp_schedule_event(time(), 'daily', 'vontmnt_theme_updater_check_updates');
    }
}

add_action('vontmnt_theme_updater_check_updates', 'vontmnt_theme_updater_run_updates');

function vontmnt_theme_updater_run_updates()
{
    // Only need to check for updates once in a multisite
    if (is_multisite()) {
        global $wpdb;

        // Check if we are in the main site context
        if (!is_main_site()) {
            switch_to_blog($wpdb->siteid);
        }

        // Get the list of installed themes
        $themes = wp_get_themes();

        // Restore the current blog after getting the themes if switched
        if (!is_main_site()) {
            restore_current_blog();
        }
    } else {
        // Get the list of installed themes for single site
        $themes = wp_get_themes();
    }

    // Loop through each installed theme and check for updates
    foreach ($themes as $theme) {
        // Get the theme slug
        $theme_slug = $theme->get_stylesheet();
        // Get the installed theme version
        $installed_version = $theme->get('Version');

        // Construct the API endpoint URL with the query parameters
        $api_url = add_query_arg(
            array(
                'domain' => urlencode(parse_url(site_url(), PHP_URL_HOST)),
                'theme' => urlencode($theme_slug),
                'version' => urlencode($installed_version),
                'key' => VONTMENT_KEY,
            ),
            VONTMENT_THEMES
        );

        // Send the request to the API endpoint using wp_remote_get
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'sslverify' => false,
        ));

        if (is_wp_error($response)) {
            error_log("$theme_slug : Failed to fetch updates. " . $response->get_error_message());
            continue;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Check if the API returned a theme update
        if ($http_code == 204) {
            error_log("$theme_slug : has no updates");
        } elseif ($http_code == 401) {
            error_log("You are not authorized for the Vontainment API");
        } elseif (!empty($response_body)) {
            $response_data = json_decode($response_body, true);

            if (isset($response_data['zip_url'])) {
                $download_url = $response_data['zip_url'];

                // Download the zip file to the upload directory
                require_once ABSPATH . 'wp-admin/includes/file.php';
                $tmp_file = download_url($download_url);

                if (is_wp_error($tmp_file)) {
                    error_log("$theme_slug : Failed to download update. " . $tmp_file->get_error_message());
                    continue;
                }

                $upload_dir = wp_upload_dir();
                $theme_zip_file = $upload_dir['path'] . '/' . basename($download_url);

                // Move the downloaded file to the themes directory
                rename($tmp_file, $theme_zip_file);

                // Unzip the theme zip file
                WP_Filesystem();
                $unzipfile = unzip_file($theme_zip_file, get_theme_root());

                // Check if the unzip was successful
                if (is_wp_error($unzipfile)) {
                    error_log('Error unzipping theme file: ' . $unzipfile->get_error_message());
                } else {
                    // Delete the theme zip file
                    unlink($theme_zip_file);
                    error_log("$theme_slug : Was updated");
                }
            } else {
                error_log("$theme_slug : Is up-to-date");
            }
        }
    }
}
