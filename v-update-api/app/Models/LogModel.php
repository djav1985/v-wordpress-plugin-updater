<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: LogModel.php
 * Description: WordPress Update API
 */

namespace App\Models;

use Doctrine\DBAL\Connection;

class LogModel
{
    public function __construct(private Connection $connection)
    {
    }
    /**
     * Insert one log row.
     */
    public function addLog(string $domain, string $type, string $status): void
    {
        $this->connection->executeStatement(
            'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
            [$domain, $type, date('Y-m-d'), $status]
        );
    }


    /**
     * Process log entries from the database and generate grouped HTML output.
     *
     * @param string $type Log type: 'plugin' or 'theme'.
     *
     * @return string
     */
    public function getLogs(string $type): string
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT domain, date, status FROM logs WHERE type = ? ORDER BY date DESC',
            [$type]
        );
        $logByDomain = [];
        foreach ($rows as $row) {
            if (!isset($logByDomain[$row['domain']])) {
                $logByDomain[$row['domain']] = [
                    'date' => $row['date'],
                    'status' => $row['status'],
                ];
            }
        }

        if (empty($logByDomain)) {
            return 'No log entries found.';
        }

        ob_start();
        echo '<div class="log-row">';
        foreach ($logByDomain as $domain => $entry) {
            $dateDiff = (strtotime(date('Y-m-d')) - strtotime($entry['date'])) / (60 * 60 * 24);
            $classes = '';
            if ($entry['status'] == 'Failed') {
                $classes .= ' error';
            } elseif ($entry['status'] == 'Success') {
                $classes .= ' success';
            }
            if ($dateDiff > 30) {
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
     * Clear all known logs.
     *
     * @return void
     */
    public function clearAllLogs(): void
    {
        $this->connection->executeStatement('DELETE FROM logs');
    }
}
