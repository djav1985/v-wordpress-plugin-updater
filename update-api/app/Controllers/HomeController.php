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

use App\Helpers\Validation;
use App\Helpers\Encryption;
use App\Core\ErrorManager;
use App\Core\Controller;
use App\Models\HostsModel;
use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use App\Core\Csrf;

class HomeController extends Controller
{
    /**
     * Handles GET requests for managing hosts.
     */
    public function handleRequest(): void
    {
        $this->render('home', [
            'hostsTableHtml' => self::getHostsTableHtml(),
        ]);
    }

    /**
     * Handles POST submissions for host actions.
     */
    public function handleSubmission(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
            header('Location: /home');
            exit();
        }

        $domain = isset($_POST['domain']) ? Validation::validateDomain($_POST['domain']) : null;
        $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
        if (isset($_POST['add_entry'])) {
            $newKey = Validation::generateKey();
            if ($domain !== null && HostsModel::addEntry($domain, $newKey)) {
                MessageHelper::addMessage('Entry added successfully.');
            } else {
                $error = 'Failed to add entry.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($_POST['regen_entry'])) {
            $newKey = Validation::generateKey();
            if ($id !== null && $domain !== null && HostsModel::updateEntry($id, $domain, $newKey)) {
                MessageHelper::addMessage('Key regenerated successfully.');
            } else {
                $error = 'Failed to regenerate key.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
            }
        } elseif (isset($_POST['delete_entry'])) {
            if ($id !== null && $domain !== null && HostsModel::deleteEntry($id, $domain)) {
                MessageHelper::addMessage('Entry deleted successfully.');
            }
        }
        header('Location: /home');
        exit();
    }

    /**
     * Generates an HTML table row for a host entry.
     */
    private static function generateHostsTableRow(int $lineNumber, string $domain, string $key): string
    {
        return '<tr>
            <form method="post" action="/home">
                <input type="hidden" name="id" value="' . htmlspecialchars((string)$lineNumber, ENT_QUOTES, 'UTF-8') . '">
                <input type="hidden" name="csrf_token" value="' .
                    htmlspecialchars(SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8') . '">
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
     */
    private static function getHostsTableHtml(): string
    {
        $entries = HostsModel::getEntries();
        $hostsTableHtml = '';
        if (count($entries) > 0) {
            $halfCount = (int) ceil(count($entries) / 2);
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
                $lineNumber = $index; // Correct line number for column 1
                $fields = explode(' ', $entry);
                $domain = isset($fields[0]) ? $fields[0] : '';
                $encryptedKey = $fields[1] ?? '';
                $key = Encryption::decrypt($encryptedKey) ?? '';
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
                $lineNumber = $index + $halfCount; // Correct line number for column 2
                $fields = explode(' ', $entry);
                $domain = isset($fields[0]) ? $fields[0] : '';
                $encryptedKey = $fields[1] ?? '';
                $key = Encryption::decrypt($encryptedKey) ?? '';
                $hostsTableHtml .= self::generateHostsTableRow($lineNumber, $domain, $key);
            }

            $hostsTableHtml .= '</tbody></table></div></div>';
        } else {
            $hostsTableHtml = "No entries found.";
        }
        return $hostsTableHtml;
    }
}
