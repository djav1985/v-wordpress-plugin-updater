<?php
/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: home-form.php
 * Description: WordPress Update API
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hosts_file = HOSTS_ACL . '/HOSTS';

    if (isset($_POST['add_entry'])) {
        $domain = $_POST['domain'];
        $key = $_POST['key'];
        $new_entry = $domain . ' ' . $key;
        file_put_contents($hosts_file, $new_entry . "\n", FILE_APPEND | LOCK_EX);
    } elseif (isset($_POST['update_entry'])) {
        $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
        $line_number = $_POST['id'];
        $domain = $_POST['domain'];
        $key = $_POST['key'];
        $entries[$line_number] = $domain . ' ' . $key;
        file_put_contents($hosts_file, implode("\n", $entries) . "\n");
    } elseif (isset($_POST['delete_entry'])) {
        $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
        $line_number = $_POST['id'];
        $domain_to_delete = $_POST['domain'];
        unset($entries[$line_number]);
        file_put_contents($hosts_file, implode("\n", $entries) . "\n");

        // Log files to be updated
        $log_files = ['plugin.log', 'theme.log'];

        foreach ($log_files as $log_file) {
            $log_file_path = LOG_DIR . "/$log_file";
            if (file_exists($log_file_path)) {
                $log_entries = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $filtered_entries = array_filter($log_entries, function ($entry) use ($domain_to_delete) {
                    return strpos($entry, $domain_to_delete) !== 0;
                });
                file_put_contents($log_file_path, implode("\n", $filtered_entries) . "\n");
            }
        }
    }

    header('Location: /home');
    exit();
}
