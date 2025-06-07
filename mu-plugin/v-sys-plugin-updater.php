<?php
/*
Plugin Name: WP Plugin Updater
Plugin URI: https://vontainment.com
Description: This plugin updates your WordPress plugins.
Version: 1.2.0
Author: Vontainment
Author URI: https://vontainment.com
*/

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

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
        // Get the plugin folder name as the slug
        $plugin_slug = dirname($plugin_path);

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

        // Send the request to the API endpoint
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        $response  = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Get the response body
        $response_body = $response;

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
                $upload_dir      = wp_upload_dir();
                $tmp_file        = download_url($download_url);
                $plugin_zip_file = $upload_dir['path'] . '/' . basename($download_url);

                // Move the downloaded file to the uploads directory
                rename($tmp_file, $plugin_zip_file);

                // Load necessary WordPress upgrader classes
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

                // Use a silent upgrader skin to suppress output
                if (!class_exists('Silent_Upgrader_Skin')) {
                    class Silent_Upgrader_Skin extends WP_Upgrader_Skin
                    {
                        public $messages = array();
                        public $errors = array();
                        public function header() {}
                        public function footer() {}
                        public function feedback($string, ...$args)
                        {
                            if ($args) {
                                $string = vsprintf($string, $args);
                            }
                            $this->messages[] = $string;
                        }
                        public function error($errors)
                        {
                            if (is_wp_error($errors)) {
                                foreach ($errors->get_error_messages() as $msg) {
                                    $this->errors[] = $msg;
                                }
                            } elseif (is_string($errors)) {
                                $this->errors[] = $errors;
                            }
                        }
                        public function before() {}
                        public function after() {}
                    }
                }
                $skin = new Silent_Upgrader_Skin();
                $upgrader = new Plugin_Upgrader($skin);
                // Set the source selection to the local zip file and force overwrite
                add_filter('upgrader_package_options', function ($options) use ($plugin_zip_file) {
                    $options['package'] = $plugin_zip_file;
                    $options['clear_destination'] = true;
                    return $options;
                });
                $result = $upgrader->install($plugin_zip_file);
                remove_all_filters('upgrader_package_options');

                // Improved logging for install result
                if (is_wp_error($result)) {
                    error_log('Error updating plugin ' . $plugin_slug . ': ' . $result->get_error_message());
                } elseif ($result === true) {
                    error_log("$plugin_slug : Was updated using Plugin_Upgrader");
                } elseif (!empty($skin->errors)) {
                    error_log("$plugin_slug : " . implode('; ', $skin->errors));
                } else {
                    error_log("$plugin_slug : Is up-to-date");
                }

                // Delete the plugin zip file
                unlink($plugin_zip_file);
            } else {
                error_log("$plugin_slug : Is up-to-date");
            }
        }
    }
}
