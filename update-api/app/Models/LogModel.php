<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: LogModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

class LogModel
{
    public static string $dir = LOG_DIR;

    /**
     * Process a log file and generate grouped output.
     *
     * @param string $logFile
     *
     * @return string
     */
    public static function processLogFile(string $logFile): string
    {
        $log_file_path = self::$dir . "/$logFile";
        $output = '';
        if (file_exists($log_file_path)) {
            $log_array = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $log_by_domain = [];
            foreach ($log_array as $entry) {
                list($domain, $date, $status) = explode(' ', $entry, 3);
                $log_by_domain[$domain] = [
                                           'date'   => $date,
                                           'status' => $status,
                                          ];
            }

            ob_start();
            echo '<div class="log-row">';
            foreach ($log_by_domain as $domain => $entry) {
                $date_diff = (strtotime(date('Y-m-d')) - strtotime($entry['date'])) / (60 * 60 * 24);
                $classes = '';
                if ($entry['status'] == 'Failed') {
                    $classes .= ' error';
                } elseif ($entry['status'] == 'Success') {
                    $classes .= ' success';
                }
                if ($date_diff > 30) {
                    $classes .= ' lost';
                }
                $classes = trim($classes);
                echo '<div class="log-sub-box' . ($classes ? " $classes" : '') . '">';
                echo '<h3>' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '</h3>';
                if ($entry['status'] == 'Failed') {
                    echo '<p class="log-entry" style="color:red;">' .
                        htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8') . ' ' .
                        htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') .
                        '</p>';
                } else {
                    echo '<p class="log-entry" style="color:green;">' .
                        htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8') . ' ' .
                        htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') .
                        '</p>';
                }
                echo '</div>';
            }
            echo '</div>';
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }

        return 'Log file not found.';
    }
}
