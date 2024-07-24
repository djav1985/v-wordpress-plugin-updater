<?php
/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: logs-helper.php
 * Description: WordPress Update API
 */

function processLogFile($logFile)
{
    $log_file_path = LOG_DIR . "/$logFile"; // path to the log file
    $output = ''; // Variable to store the output

    if (file_exists($log_file_path)) {
        // read the log file into an array
        $log_array = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // group the log entries by domain name
        $log_by_domain = [];
        foreach ($log_array as $entry) {
            list($domain, $date, $status) = explode(' ', $entry);
            $log_by_domain[$domain] = ['date' => $date, 'status' => $status];
        }

        // Start the output buffering
        ob_start();

        echo '<div class="log-row">';

        foreach ($log_by_domain as $domain => $entry) {
            // Calculate the difference in days from the log entry date to now
            $date_diff = (strtotime(date('Y-m-d')) - strtotime($entry['date'])) / (60 * 60 * 24);
            $classes = '';

            // Add classes based on the status and date difference
            if ($entry['status'] == 'Failed') {
                $classes .= ' error';
            } elseif ($entry['status'] == 'Success') {
                $classes .= ' success';
            }

            if ($date_diff > 30) {
                $classes .= ' lost';
            }

            // Trim any extra spaces from the classes string
            $classes = trim($classes);

            // display the entry for each domain
            echo '<div class="log-sub-box' . ($classes ? " $classes" : '') . '">';
            echo '<h3>' . $domain . '</h3>';
            if ($entry['status'] == 'Failed') {
                echo '<p class="log-entry" style="color:red;">' . $entry['date'] . ' ' . $entry['status'] . '</p>';
            } else {
                echo '<p class="log-entry" style="color:green;">' . $entry['date'] . ' ' . $entry['status'] . '</p>';
            }
            echo '</div>';
        }

        echo '</div>';

        // store the buffered output into the $output variable
        $output = ob_get_contents();

        // end output buffering
        ob_end_clean();

        // Now, $output variable contains the output
        return $output;
    } else {
        return 'Log file not found.';
    }
}

$ploutput = processLogFile('plugin.log');
$thoutput = processLogFile('theme.log');
