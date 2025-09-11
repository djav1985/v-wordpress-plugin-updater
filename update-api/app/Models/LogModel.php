<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: LogModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use App\Core\DatabaseManager;

class LogModel
{

    /**
     * Process a log file and generate grouped output.
     *
     * @param string $logFile
     *
     * @return string
     */
    public static function processLogFile(string $logFile): string
    {
        $type = strpos($logFile, 'theme') !== false ? 'theme' : 'plugin';
        $conn = DatabaseManager::getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT domain, date, status FROM logs WHERE type = ? ORDER BY date DESC',
            [$type]
        );
        $log_by_domain = [];
        foreach ($rows as $row) {
            if (!isset($log_by_domain[$row['domain']])) {
                $log_by_domain[$row['domain']] = [
                    'date' => $row['date'],
                    'status' => $row['status'],
                ];
            }
        }

        if (empty($log_by_domain)) {
            return 'Log file not found.';
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
            $color = $entry['status'] == 'Failed' ? 'red' : 'green';
            echo '<p class="log-entry" style="color:' . $color . ';">' .
                htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8') . ' ' .
                htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') .
                '</p>';
            echo '</div>';
        }
        echo '</div>';
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * Clear the contents of a log file without deleting it.
     *
     * @param string $logFile The log file name.
     *
     * @return bool True on success, false otherwise.
     */
    public static function clearLogFile(string $logFile): bool
    {
        $type = strpos($logFile, 'theme') !== false ? 'theme' : 'plugin';
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DELETE FROM logs WHERE type = ?', [$type]);
        return true;
    }

    /**
     * Clear all known logs.
     *
     * @return void
     */
    public static function clearAllLogs(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DELETE FROM logs');
    }
}
