<?php
/*
Plugin Name: WP Plugin Updater
Description: This plugin updates your WordPress plugins.
Author: Vontainment
Author URI: https://vontainment.com
Version: 2.0.0
*/

// Schedule the update check to run every day
add_action('wp', 'vontmnt_plugin_updater_schedule_updates');

function vontmnt_plugin_updater_schedule_updates()
{
    if (!wp_next_scheduled('vontmnt_plugin_updater_check_updates')) {
        wp_schedule_event(time(), 'daily', 'vontmnt_plugin_updater_check_updates');
    }
}

add_action('vontmnt_plugin_updater_check_updates', 'vontmnt_plugin_updater_run_updates');

function vontmnt_plugin_updater_run_updates()
{
    // Get the list of installed plugins
    $plugins = get_plugins();

    // Loop through each installed plugin and check for updates
    foreach ($plugins as $plugin_path => $plugin) {
        // Get the plugin slug
        $plugin_slug = basename($plugin_path, '.php');
        // Get the installed plugin version
        $installed_version = $plugin['Version'];

        // Construct the API endpoint URL with the query parameters
        $api_url = add_query_arg(
            array(
                'domain' => urlencode(parse_url(site_url(), PHP_URL_HOST)),
                'plugin' => urlencode($plugin_slug),
                'version' => urlencode($installed_version),
                'key' => VONTMENT_KEY,
            ),
            VONTMENT_PLUGINS
        );

        // Send the request to the API endpoint using wp_remote_get
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'sslverify' => false,
        ));

        if (is_wp_error($response)) {
            error_log("$plugin_slug : Failed to fetch updates. " . $response->get_error_message());
            continue;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Check if the API returned a plugin update
        if ($http_code == 204) {
            error_log("$plugin_slug : has no updates");
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
                    error_log("$plugin_slug : Failed to download update. " . $tmp_file->get_error_message());
                    continue;
                }

                $upload_dir = wp_upload_dir();
                $plugin_zip_file = $upload_dir['path'] . '/' . basename($download_url);

                // Move the downloaded file to the plugins directory
                rename($tmp_file, $plugin_zip_file);

                // Unzip the plugin zip file
                WP_Filesystem();
                $unzipfile = unzip_file($plugin_zip_file, WP_PLUGIN_DIR);

                // Check if the unzip was successful
                if (is_wp_error($unzipfile)) {
                    error_log('Error unzipping plugin file: ' . $unzipfile->get_error_message());
                } else {
                    // Delete the plugin zip file
                    unlink($plugin_zip_file);
                    error_log("$plugin_slug : Was updated");
                }
            } else {
                error_log("$plugin_slug : Is up-to-date");
            }
        }
    }
}
