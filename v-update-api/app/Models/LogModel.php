<?php

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

use App\Core\DatabaseManager;

class LogModel
{
    /**
     * Insert one log row.
     *
     * @param string $domain Domain name.
     * @param string $type   Log type (e.g., 'plugin', 'theme').
     * @param string $status Status (e.g., 'Success', 'Failed').
     * @return void
     */
    public static function addLog(string $domain, string $type, string $status): void
    {
        $connection = DatabaseManager::getInstance()->getConnection();
        $connection->executeStatement(
            'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
            [$domain, $type, date('Y-m-d'), $status]
        );
    }


    /**
     * Return the latest log entry per domain for display.
     *
     * @param string $type Log type (e.g., 'plugin', 'theme').
     * @return list<array{domain: string, date: string, status: string, age_days: int}>
     */
    public static function getLogs(string $type): array
    {
        $connection = DatabaseManager::getInstance()->getConnection();
        $rows = $connection->fetchAllAssociative(
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

        $logs = [];
        foreach ($logByDomain as $domain => $entry) {
            $dateDiff = (strtotime(date('Y-m-d')) - strtotime($entry['date'])) / (60 * 60 * 24);
            $logs[] = [
                'domain' => $domain,
                'date' => $entry['date'],
                'status' => $entry['status'],
                'age_days' => (int) $dateDiff,
            ];
        }

        return $logs;
    }

    /**
     * Clear all known logs.
     *
     * @return void
     */
    public static function clearAllLogs(): void
    {
        DatabaseManager::getInstance()->getConnection()->executeStatement('DELETE FROM logs');
    }
}
