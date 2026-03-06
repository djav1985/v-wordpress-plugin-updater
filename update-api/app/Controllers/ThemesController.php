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
use App\Core\Response;

class ThemesController extends Controller
{
    /**
     * Handles GET requests for theme-related actions.
     */
    public function handleRequest(): Response
    {
        $themesTableHtml = self::getThemesTableHtml();
        $hosts = \App\Models\HostsModel::getHosts();
        return Response::view('thupdate', [
            'themesTableHtml' => $themesTableHtml,
            'hosts' => $hosts,
        ]);
    }

    /**
     * Handles POST submissions for theme-related actions.
     */
    public function handleSubmission(): Response
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::getInstance()->log($error);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                return Response::text($error, 400);
            }
            MessageHelper::addMessage($error);
            return Response::redirect('/');
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if (isset($_FILES['theme_file'])) {
            $messages = ThemeModel::uploadFiles($_FILES['theme_file'], $isAjax);
            if ($isAjax) {
                return Response::text(implode("\n", $messages));
            }
            foreach ($messages as $message) {
                MessageHelper::addMessage($message);
            }
            return Response::redirect('/thupdate');
        } elseif (isset($_POST['delete_theme'])) {
            $themeName = isset($_POST['theme_name']) ? ValidationHelper::validateSlug($_POST['theme_name']) : null;
            if ($themeName !== null && ThemeModel::deleteTheme($themeName)) {
                MessageHelper::addMessage('Theme deleted successfully!');
            } else {
                $error = 'Failed to delete theme file. Please try again.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
            }
            return Response::redirect('/thupdate');
        } elseif (isset($_POST['install_theme'])) {
            $themeName = isset($_POST['theme_name'])
                ? ValidationHelper::validateSlug($_POST['theme_name'])
                : null;
            $domain = isset($_POST['domain']) ? ValidationHelper::validateDomain($_POST['domain']) : null;

            if ($themeName === null || $domain === null) {
                $error = 'Invalid theme name or domain.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
                return Response::redirect('/thupdate');
            }

            $result = self::installThemeToDomain($themeName, $domain);
            MessageHelper::addMessage($result['message']);
            return Response::redirect('/thupdate');
        }
        return Response::redirect('/thupdate');
    }

    /**
     * Install a theme to a specific domain via REST API.
     *
     * @param string $themeName The theme slug_version
     * @param string $domain The target domain
     * @return array{success: bool, message: string}
     */
    private static function installThemeToDomain(string $themeName, string $domain): array
    {
        $themePath = ThemeModel::$dir . '/' . basename($themeName);
        
        if (!file_exists($themePath)) {
            return ['success' => false, 'message' => 'Theme file not found.'];
        }
        
        // Get the API key for the domain
        $conn = \App\Core\DatabaseManager::getConnection();
        $keyEncrypted = $conn->fetchOne('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        
        if (!$keyEncrypted) {
            return ['success' => false, 'message' => 'Domain not found in hosts table.'];
        }
        
        $key = \App\Helpers\EncryptionHelper::decrypt($keyEncrypted);
        
        // Prepare the API request
        $url = 'https://' . $domain . '/wp-json/vwpd/v1/themes';
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $key,
            ],
            CURLOPT_POSTFIELDS => [
                'package' => new \CURLFile($themePath, 'application/zip', basename($themeName)),
            ],
            CURLOPT_TIMEOUT => 300,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Theme installed successfully to ' . $domain];
        } else {
            $errorMsg = $response ?: 'Failed to install theme';
            return ['success' => false, 'message' => 'Failed to install theme to ' . $domain . ': ' . $errorMsg];
        }
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
        return '<tr>
             <td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
             <td>' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</td>
             <td>
                 <button class="th-submit action-btn" type="button" onclick="openThemeActionModal(\'' .
                     htmlspecialchars($themeFile, ENT_QUOTES, 'UTF-8') . '\', \'' .
                     htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '\')">Action</button>
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
