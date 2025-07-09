<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ThemesController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Utility;
use App\Core\ErrorMiddleware;
use App\Core\Controller;
use App\Models\ThemeModel;

class ThemesController extends Controller
{
    /**
     * Handles the incoming request for theme-related actions.
     *
     * Validates CSRF tokens and determines whether to upload or delete themes.
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
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if (isset($_FILES['theme_file'])) {
                    $messages = ThemeModel::uploadFiles($_FILES['theme_file'], $isAjax);
                    if ($isAjax) {
                        echo implode("\n", $messages);
                        exit();
                    }
                    $_SESSION['messages'] = array_merge($_SESSION['messages'] ?? [], $messages);
                    header('Location: /thupdate');
                    exit();
                } elseif (isset($_POST['delete_theme'])) {
                    $theme_name = isset($_POST['theme_name']) ? Utility::validateSlug($_POST['theme_name']) : null;
                    if ($theme_name !== null && ThemeModel::deleteTheme($theme_name)) {
                        $_SESSION['messages'][] = 'Theme deleted successfully!';
                    } else {
                        $error = 'Failed to delete theme file. Please try again.';
                        ErrorMiddleware::logMessage($error);
                        $_SESSION['messages'][] = $error;
                    }
                    header('Location: /thupdate');
                    exit();
                }
            } else {
                $error = 'Invalid Form Action.';
                ErrorMiddleware::logMessage($error);
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    http_response_code(400);
                    echo $error;
                    exit();
                }
                $_SESSION['messages'][] = $error;
                header('Location: /');
                exit();
            }
        }

        $themesTableHtml = self::getThemesTableHtml();

        // Render the thupdate view
        (new self())->render('thupdate', [
            'themesTableHtml' => $themesTableHtml,
        ]);
    }


    /**
     * Generates an HTML table row for a theme.
     *
     * @param string $theme      The theme file path.
     * @param string $theme_name The name of the theme.
     *
     * @return string The HTML table row for the theme.
     */
    public static function generateThemeTableRow(string $theme, string $theme_name): string
    {
        return '<tr>
             <td>' . htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8') . '</td>
             <td>
                 <form name="delete_theme_form" action="/thupdate" method="POST">
                     <input type="hidden" name="theme_name" value="' .
                         htmlspecialchars($theme, ENT_QUOTES, 'UTF-8') .
                     '">
                     <input type="hidden" name="csrf_token" value="' .
                         htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">
                     <input class="th-submit" type="submit" name="delete_theme" value="Delete">
                 </form>
             </td>
         </tr>';
    }

    /**
     * Generates the HTML for the themes table.
     *
     * Retrieves all theme files and organizes them into two columns for display.
     *
     * @return string The HTML for the themes table.
     */
    public static function getThemesTableHtml(): string
    {
        $themes = ThemeModel::getThemes();
        if (count($themes) > 0) {
            $half_count = ceil(count($themes) / 2);
            $themes_column1 = array_slice($themes, 0, $half_count);
            $themes_column2 = array_slice($themes, $half_count);
            $themesTableHtml = '<div class="row"><div class="column">
                 <table>
                     <thead>
                         <tr>
                             <th>Theme Name</th>
                             <th>Delete</th>
                         </tr>
                     </thead>
                     <tbody>';
            foreach ($themes_column1 as $theme) {
                $theme_name = basename($theme);
                $themesTableHtml .= self::generateThemeTableRow($theme, $theme_name);
            }
            $themesTableHtml .= '</tbody></table></div><div class="column"><table>
                 <thead>
                     <tr>
                         <th>Theme Name</th>
                         <th>Delete</th>
                     </tr>
                 </thead>
                 <tbody>';
            foreach ($themes_column2 as $theme) {
                $theme_name = basename($theme);
                $themesTableHtml .= self::generateThemeTableRow($theme, $theme_name);
            }
            $themesTableHtml .= '</tbody></table></div></div>';
        } else {
            $themesTableHtml = "No themes found.";
        }
        return $themesTableHtml;
    }
}
