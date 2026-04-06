<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: ThemesController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Helpers\ValidationHelper;
use App\Core\ErrorManager;
use App\Core\Controller;
use App\Models\ThemeModel;
use App\Helpers\MessageHelper;
use App\Core\ResponseManager;

class ThemesController extends Controller
{
    /**
     * Handles GET requests for theme-related actions.
     */
    public function handleRequest(): ResponseManager
    {
        $themesTableHtml = self::getThemesTableHtml();
        return ResponseManager::view('thupdate', [
            'themesTableHtml' => $themesTableHtml,
        ]);
    }

    /**
     * Handles POST submissions for theme-related actions.
     */
    public function handleSubmission(): ResponseManager
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                return ResponseManager::text($error, 400);
            }
            MessageHelper::addMessage($error);
            return ResponseManager::redirect('/thupdate');
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if (isset($_FILES['theme_file'])) {
            $messages = ThemeModel::uploadFiles($_FILES['theme_file'], $isAjax);
            if ($isAjax) {
                return ResponseManager::text(implode("\n", $messages));
            }
            foreach ($messages as $message) {
                MessageHelper::addMessage($message);
            }
            return ResponseManager::redirect('/thupdate');
        } elseif (isset($_POST['delete_theme'])) {
            $themeName = isset($_POST['theme_name']) ? ValidationHelper::validateSlug($_POST['theme_name']) : null;
            if ($themeName !== null && ThemeModel::deleteTheme($themeName)) {
                MessageHelper::addMessage('Theme deleted successfully!');
            } else {
                $error = 'Failed to delete theme file. Please try again.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
            }
            return ResponseManager::redirect('/thupdate');
        }
        return ResponseManager::redirect('/thupdate');
    }

    /**
     * Generates an HTML table row for a theme.
     * @param array{slug: string, version: string} $theme
     */
    private static function generateThemeTableRow(array $theme): string
    {
        $name = str_replace(['-', '_'], ' ', $theme['slug']);
        $version = $theme['version'];
        $themeFile = $theme['slug'] . '_' . $version . '.zip';
        $csrfToken = \App\Core\SessionManager::getInstance()->get('csrf_token') ?? '';
        return '<tr>
             <td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
             <td>' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</td>
             <td>
                 <form method="POST" action="/thupdate" class="inline-action-form">
                     <input type="hidden" name="csrf_token" value="' .
                         htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') . '">
                     <input type="hidden" name="theme_name" value="' .
                         htmlspecialchars($themeFile, ENT_QUOTES, 'UTF-8') . '">
                     <button class="th-submit red-button" type="submit" name="delete_theme">Delete</button>
                 </form>
             </td>
         </tr>';
    }

    /**
     * Generates the HTML for the themes table.
     */
    private static function getThemesTableHtml(): string
    {
        $themes = ThemeModel::getThemes();
        if (count($themes) > 0) {
            $halfCount = (int) ceil(count($themes) / 2);
            $themesColumn1 = array_slice($themes, 0, $halfCount);
            $themesColumn2 = array_slice($themes, $halfCount);
            $themesTableHtml = '<div class="row"><div class="column">
                 <table>
                     <thead>
                         <tr>
                             <th>Name</th>
                             <th>Version</th>
                             <th>Action</th>
                         </tr>
                     </thead>
                     <tbody>';
            foreach ($themesColumn1 as $theme) {
                $themesTableHtml .= self::generateThemeTableRow($theme);
            }
            $themesTableHtml .= '</tbody></table></div><div class="column"><table>
                 <thead>
                     <tr>
                         <th>Name</th>
                         <th>Version</th>
                         <th>Action</th>
                     </tr>
                 </thead>
                 <tbody>';
            foreach ($themesColumn2 as $theme) {
                $themesTableHtml .= self::generateThemeTableRow($theme);
            }
            $themesTableHtml .= '</tbody></table></div></div>';
        } else {
            $themesTableHtml = "No themes found.";
        }
        return $themesTableHtml;
    }
}
