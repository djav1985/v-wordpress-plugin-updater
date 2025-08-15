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

use App\Helpers\Validation;
use App\Core\ErrorManager;
use App\Core\Controller;
use App\Models\ThemeModel;
use App\Helpers\MessageHelper;
use App\Core\Csrf;
use App\Core\SessionManager;

class ThemesController extends Controller
{
    /**
     * Handles GET requests for theme-related actions.
     */
    public function handleRequest(): void
    {
        $themesTableHtml = self::getThemesTableHtml();
        $this->render('thupdate', [
            'themesTableHtml' => $themesTableHtml,
        ]);
    }

    /**
     * Handles POST submissions for theme-related actions.
     */
    public function handleSubmission(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                http_response_code(400);
                echo $error;
                exit();
            }
            MessageHelper::addMessage($error);
            header('Location: /');
            exit();
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if (isset($_FILES['theme_file'])) {
            $messages = ThemeModel::uploadFiles($_FILES['theme_file'], $isAjax);
            if ($isAjax) {
                echo implode("\n", $messages);
                exit();
            }
            foreach ($messages as $message) {
                MessageHelper::addMessage($message);
            }
            header('Location: /thupdate');
            exit();
        } elseif (isset($_POST['delete_theme'])) {
            $theme_name = isset($_POST['theme_name']) ? Validation::validateSlug($_POST['theme_name']) : null;
            if ($theme_name !== null && ThemeModel::deleteTheme($theme_name)) {
                MessageHelper::addMessage('Theme deleted successfully!');
            } else {
                $error = 'Failed to delete theme file. Please try again.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
            }
            header('Location: /thupdate');
            exit();
        }
    }

    /**
     * Generates an HTML table row for a theme.
     */
    private static function generateThemeTableRow(array $theme): string
    {
        $name = str_replace(['-', '_'], ' ', $theme['slug']);
        $version = $theme['version'];
        return '<tr>
             <td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
             <td>' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</td>
             <td>
                 <form name="delete_theme_form" action="/thupdate" method="POST">
                     <input type="hidden" name="theme_name" value="' .
                         htmlspecialchars($theme['slug'], ENT_QUOTES, 'UTF-8') .
                     '">
                     <input type="hidden" name="csrf_token" value="' .
                         htmlspecialchars(SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8') . '">
                     <input class="th-submit" type="submit" name="delete_theme" value="Delete">
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
            $half_count = (int) ceil(count($themes) / 2);
            $themes_column1 = array_slice($themes, 0, $half_count);
            $themes_column2 = array_slice($themes, $half_count);
            $themesTableHtml = '<div class="row"><div class="column">
                 <table>
                     <thead>
                         <tr>
                             <th>Name</th>
                             <th>Version</th>
                             <th>Delete</th>
                         </tr>
                     </thead>
                     <tbody>';
            foreach ($themes_column1 as $theme) {
                if (is_string($theme)) {
                    // Legacy: parse filename like slug_version.zip
                    if (preg_match('/^(.+)_([\d\.]+)\.zip$/', basename($theme), $matches)) {
                        $theme = [
                            'slug' => $matches[1],
                            'version' => $matches[2],
                        ];
                    } else {
                        continue;
                    }
                }
                $themesTableHtml .= self::generateThemeTableRow($theme);
            }
            $themesTableHtml .= '</tbody></table></div><div class="column"><table>
                 <thead>
                     <tr>
                         <th>Name</th>
                         <th>Version</th>
                         <th>Delete</th>
                     </tr>
                 </thead>
                 <tbody>';
            foreach ($themes_column2 as $theme) {
                if (is_string($theme)) {
                    if (preg_match('/^(.+)_([\d\.]+)\.zip$/', basename($theme), $matches)) {
                        $theme = [
                            'slug' => $matches[1],
                            'version' => $matches[2],
                        ];
                    } else {
                        continue;
                    }
                }
                $themesTableHtml .= self::generateThemeTableRow($theme);
            }
            $themesTableHtml .= '</tbody></table></div></div>';
        } else {
            $themesTableHtml = "No themes found.";
        }
        return $themesTableHtml;
    }
}
