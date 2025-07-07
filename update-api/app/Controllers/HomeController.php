<?php

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 * @version 3.0.0
 *
 * File: HomeController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\UtilityHandler;
use App\Core\ErrorHandler;

class HomeController
{
    /**
     * Handles the incoming request for managing hosts.
     *
     * Validates CSRF tokens and determines whether to add, update, or delete entries.
     *
     * @return void
     */
    public static function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        } elseif (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $domain = isset($_POST['domain']) ? UtilityHandler::validateDomain($_POST['domain']) : null;
            $key = isset($_POST['key']) ? UtilityHandler::validateKey($_POST['key']) : null;
            $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
            if (isset($_POST['add_entry'])) {
                self::addEntry($domain, $key);
            } elseif (isset($_POST['update_entry'])) {
                self::updateEntry($id, $domain, $key);
            } elseif (isset($_POST['delete_entry'])) {
                self::deleteEntry($id, $domain);
            }
        } else {
            $error = 'Invalid Form Action.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
            header('Location: /');
            exit();
        }
    }

    /**
     * Adds a new entry to the hosts file.
     *
     * @param string|null $domain The domain to add.
     * @param string|null $key    The key associated with the domain.
     *
     * @return void
     */
    private static function addEntry(?string $domain, ?string $key): void
    {
        $hosts_file = HOSTS_ACL . '/HOSTS';
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $new_entry = $safe_domain . ' ' . $safe_key;
        if (file_put_contents($hosts_file, $new_entry . "\n", FILE_APPEND | LOCK_EX) === false) {
            $error = 'Failed to add entry.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
        } else {
            $_SESSION['messages'][] = 'Entry added successfully.';
        }
        header('Location: /home');
        exit();
    }

    /**
     * Updates an existing entry in the hosts file.
     *
     * @param int|null    $line_number The line number of the entry to update.
     * @param string|null $domain      The updated domain.
     * @param string|null $key         The updated key.
     *
     * @return void
     */
    private static function updateEntry(?int $line_number, ?string $domain, ?string $key): void
    {
        $hosts_file = HOSTS_ACL . '/HOSTS';
        $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $entries[$line_number] = $safe_domain . ' ' . $safe_key;
        if (file_put_contents($hosts_file, implode("\n", $entries) . "\n") === false) {
            $error = 'Failed to update entry.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
        } else {
            $_SESSION['messages'][] = 'Entry updated successfully.';
        }
        header('Location: /home');
        exit();
    }

    /**
     * Deletes an entry from the hosts file and updates related log files.
     *
     * @param int|null    $line_number       The line number of the entry to delete.
     * @param string|null $domain_to_delete  The domain to delete from the logs.
     *
     * @return void
     */
    private static function deleteEntry(?int $line_number, ?string $domain_to_delete): void
    {
        if (
            !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $error = 'Invalid CSRF token.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
            header('Location: /home');
            exit();
        }

        $hosts_file = HOSTS_ACL . '/HOSTS';
        $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
        unset($entries[$line_number]);
        if (file_put_contents($hosts_file, implode("\n", $entries) . "\n") === false) {
            $error = 'Failed to delete entry.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
        }

        $log_files = [
                      'plugin.log',
                      'theme.log',
                     ];
        $safe_domain_to_delete = htmlspecialchars($domain_to_delete, ENT_QUOTES, 'UTF-8');
        foreach ($log_files as $log_file) {
            $log_file_path = LOG_DIR . "/$log_file";
            if (file_exists($log_file_path)) {
                $log_entries = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $filtered_entries = array_filter($log_entries, function ($entry) use ($safe_domain_to_delete) {
                    return strpos($entry, $safe_domain_to_delete) !== 0;
                });
                if (file_put_contents($log_file_path, implode("\n", $filtered_entries) . "\n") === false) {
                    $error = 'Failed to update log file ' . $log_file_path;
                    ErrorHandler::logMessage($error);
                    $_SESSION['messages'][] = $error;
                }
            }
        }
        $_SESSION['messages'][] = 'Entry deleted successfully.';
        header('Location: /home');
        exit();
    }

    /**
     * Generates an HTML table row for a host entry.
     *
     * @param int    $lineNumber The line number of the entry.
     * @param string $domain     The domain of the entry.
     * @param string $key        The key of the entry.
     *
     * @return string The HTML table row for the host entry.
     */
    public static function generateHostsTableRow(int $lineNumber, string $domain, string $key): string
    {
        return '<tr>
            <form method="post" action="/home">
                <input type="hidden" name="id" value="' . htmlspecialchars($lineNumber, ENT_QUOTES, 'UTF-8') . '">
                <input type="hidden" name="csrf_token" value="' .
                    htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">
                <td><input class="hosts-domain" type="text" name="domain" value="' .
                htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') .
            '"></td>
                <td>
                    <input class="hosts-key" type="text" name="key" value="' .
                    htmlspecialchars($key, ENT_QUOTES, 'UTF-8') .
                '">
                </td>
                <td>
                    <input class="hosts-submit" type="submit" name="update_entry" value="Update">
                    <input class="hosts-submit" type="submit" name="delete_entry" value="Delete">
                </td>
            </form>
        </tr>';
    }

    /**
     * Generates the hosts table HTML for display.
     *
     * Retrieves all host entries and organizes them into two columns for display.
     *
     * @return string The HTML for the hosts table.
     */
    public static function getHostsTableHtml(): string
    {
        $hostsFile = HOSTS_ACL . '/HOSTS';
        $entries = file_exists($hostsFile) ? file($hostsFile, FILE_IGNORE_NEW_LINES) : [];
        $hostsTableHtml = '';
        if (count($entries) > 0) {
            $halfCount = ceil(count($entries) / 2);
            $entriesColumn1 = array_slice($entries, 0, $halfCount);
            $entriesColumn2 = array_slice($entries, $halfCount);
            $hostsTableHtml .= '<div class="row">';
            // Column 1
            $hostsTableHtml .= '<div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Key</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($entriesColumn1 as $index => $entry) {
                $lineNumber = $index;
            // Correct line number for column 1
                $fields = explode(' ', $entry);
                $domain = isset($fields[0]) ? $fields[0] : '';
                $key = isset($fields[1]) ? $fields[1] : '';
                $hostsTableHtml .= self::generateHostsTableRow($lineNumber, $domain, $key);
            }

            $hostsTableHtml .= '</tbody></table></div>';
            // Column 2
            $hostsTableHtml .= '<div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Key</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($entriesColumn2 as $index => $entry) {
                $lineNumber = $index + $halfCount;
        // Correct line number for column 2
                $fields = explode(' ', $entry);
                $domain = isset($fields[0]) ? $fields[0] : '';
                $key = isset($fields[1]) ? $fields[1] : '';
                $hostsTableHtml .= self::generateHostsTableRow($lineNumber, $domain, $key);
            }

            $hostsTableHtml .= '</tbody></table></div></div>';
        } else {
            $hostsTableHtml = "No entries found.";
        }
        return $hostsTableHtml;
    }
}
