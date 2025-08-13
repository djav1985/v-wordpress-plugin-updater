<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: HomeController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Utility;
use App\Core\ErrorMiddleware;
use App\Core\Controller;
use App\Models\HostsModel;

class HomeController extends Controller
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
                hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
            ) {
                $domain = isset($_POST['domain']) ? Utility::validateDomain($_POST['domain']) : null;
                $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
                if (isset($_POST['add_entry'])) {
                    $newKey = Utility::generateKey();
                    if ($domain !== null && HostsModel::addEntry($domain, $newKey)) {
                        $_SESSION['messages'][] = 'Entry added successfully.';
                    } else {
                        $error = 'Failed to add entry.';
                        ErrorMiddleware::logMessage($error);
                        $_SESSION['messages'][] = $error;
                    }
                } elseif (isset($_POST['regen_entry'])) {
                    $newKey = Utility::generateKey();
                    if ($id !== null && $domain !== null && HostsModel::updateEntry($id, $domain, $newKey)) {
                        $_SESSION['messages'][] = 'Key regenerated successfully.';
                    } else {
                        $error = 'Failed to regenerate key.';
                        ErrorMiddleware::logMessage($error);
                        $_SESSION['messages'][] = $error;
                    }
                } elseif (isset($_POST['delete_entry'])) {
                    if ($id !== null && $domain !== null && HostsModel::deleteEntry($id, $domain)) {
                        $_SESSION['messages'][] = 'Entry deleted successfully.';
                    }
                }
                // If no action triggered, redirect back to home
                header('Location: /home');
                exit();
            } else {
                $error = 'Invalid Form Action.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
                header('Location: /home');
                exit();
            }
        }

        // Render the home view
        (new self())->render('home', [
            'hostsTableHtml' => self::getHostsTableHtml(),
        ]);
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
                    <input class="hosts-key" type="text" value="' .
                    htmlspecialchars($key, ENT_QUOTES, 'UTF-8') .
                '" readonly>
                </td>
                <td>
                    <input class="hosts-submit" type="submit" name="regen_entry" value="Regen">
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
        $entries = HostsModel::getEntries();
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
                $encryptedKey = $fields[1] ?? '';
                $key = Utility::decrypt($encryptedKey) ?? '';
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
                $encryptedKey = $fields[1] ?? '';
                $key = Utility::decrypt($encryptedKey) ?? '';
                $hostsTableHtml .= self::generateHostsTableRow($lineNumber, $domain, $key);
            }

            $hostsTableHtml .= '</tbody></table></div></div>';
        } else {
            $hostsTableHtml = "No entries found.";
        }
        return $hostsTableHtml;
    }
}
