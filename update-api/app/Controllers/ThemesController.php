<?php

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 * @version 3.0.0
 *
 * File: ThemesController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\UtilityHandler;
use App\Core\ErrorHandler;

class ThemesController // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        } elseif (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            if (isset($_FILES['theme_file'])) {
                self::uploadThemeFiles();
            } elseif (isset($_POST['delete_theme'])) {
                $theme_name = isset($_POST['theme_name']) ? UtilityHandler::validateSlug($_POST['theme_name']) : null;
                self::deleteTheme($theme_name);
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
     * Uploads theme files to the server.
     *
     * Validates file extensions, removes existing themes with the same slug, and moves the uploaded files.
     *
     * @return void
     */
    private static function uploadThemeFiles(): void
    {
        $allowed_extensions = ['zip'];
        $total_files = count($_FILES['theme_file']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($_FILES['theme_file']['name'][$i])
                ? UtilityHandler::validateFilename($_FILES['theme_file']['name'][$i])
                : '';
            $file_tmp = isset($_FILES['theme_file']['tmp_name'][$i])
                ? $_FILES['theme_file']['tmp_name'][$i]
                : '';
            $file_size = isset($_FILES['theme_file']['size'][$i])
                ? filter_var($_FILES['theme_file']['size'][$i], FILTER_VALIDATE_INT)
                : 0;
            $file_error = isset($_FILES['theme_file']['error'][$i])
                ? filter_var($_FILES['theme_file']['error'][$i], FILTER_VALIDATE_INT)
                : UPLOAD_ERR_NO_FILE;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $theme_slug = explode('_', $file_name)[0];
            $existing_themes = glob(THEMES_DIR . '/' . $theme_slug . '_*');
            foreach ($existing_themes as $theme) {
                if (is_file($theme)) {
                    unlink($theme);
                }
            }

            if ($file_error !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
                $error = 'Error uploading: ' .
                    htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') .
                    '. Only .zip files are allowed.';
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
                continue;
            }

            $theme_path = THEMES_DIR . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $theme_path)) {
                $_SESSION['messages'][] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
            } else {
                $error = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
        }

        header('Location: /thupdate');
        exit();
    }

    /**
     * Deletes a theme file from the server.
     *
     * Validates the theme name and ensures the file exists before deletion.
     *
     * @param string|null $theme_name The name of the theme to delete.
     *
     * @return void
     */
    private static function deleteTheme(?string $theme_name): void
    {
        $theme_name = UtilityHandler::validateFilename($theme_name);
        $theme_name = basename((string) $theme_name);
        $theme_path = THEMES_DIR . '/' . $theme_name;
        if (
            file_exists($theme_path) &&
            dirname(realpath($theme_path)) === realpath(THEMES_DIR)
        ) {
            if (unlink($theme_path)) {
                $_SESSION['messages'][] = 'Theme deleted successfully!';
            } else {
                $error = 'Failed to delete theme file. Please try again.';
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
            header('Location: /thupdate');
            exit();
        }
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
        $themes = glob(THEMES_DIR . "/*.zip");
        $themes = array_reverse($themes);
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
