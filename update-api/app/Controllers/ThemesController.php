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
        $hosts = \App\Models\HostsModel::getHosts();
        $this->render('thupdate', [
            'themesTableHtml' => $themesTableHtml,
            'hosts' => $hosts,
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
        } elseif (isset($_POST['install_theme'])) {
            $theme_name = isset($_POST['theme_name'])
                ? Validation::validateSlug($_POST['theme_name'])
                : null;
            $domain = isset($_POST['domain']) ? $_POST['domain'] : null;
            
            if ($theme_name === null || $domain === null) {
                $error = 'Invalid theme name or domain.';
                ErrorManager::getInstance()->log($error);
                MessageHelper::addMessage($error);
                header('Location: /thupdate');
                exit();
            }
            
            $result = self::installThemeToDomain($theme_name, $domain);
            MessageHelper::addMessage($result['message']);
            header('Location: /thupdate');
            exit();
        }
    }

    /**
     * Install a theme to a specific domain via REST API.
     *
     * @param string $theme_name The theme slug_version
     * @param string $domain The target domain
     * @return array{success: bool, message: string}
     */
    private static function installThemeToDomain(string $theme_name, string $domain): array
    {
        $theme_path = ThemeModel::$dir . '/' . basename($theme_name);
        
        if (!file_exists($theme_path)) {
            return ['success' => false, 'message' => 'Theme file not found.'];
        }
        
        // Get the API key for the domain
        $conn = \App\Core\DatabaseManager::getConnection();
        $key_encrypted = $conn->fetchOne('SELECT key FROM hosts WHERE domain = ?', [$domain]);
        
        if (!$key_encrypted) {
            return ['success' => false, 'message' => 'Domain not found in hosts table.'];
        }
        
        $key = \App\Helpers\Encryption::decrypt($key_encrypted);
        
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
                'package' => new \CURLFile($theme_path, 'application/zip', basename($theme_name)),
            ],
            CURLOPT_TIMEOUT => 300,
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code === 200) {
            return ['success' => true, 'message' => 'Theme installed successfully to ' . $domain];
        } else {
            $error_msg = $response ?: 'Failed to install theme';
            return ['success' => false, 'message' => 'Failed to install theme to ' . $domain . ': ' . $error_msg];
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
        $theme_file = $theme['slug'] . '_' . $version . '.zip';
        return '<tr>
             <td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
             <td>' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</td>
             <td>
                 <button class="th-submit action-btn" type="button" onclick="openThemeActionModal(\'' .
                     htmlspecialchars($theme_file, ENT_QUOTES, 'UTF-8') . '\', \'' .
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
            $half_count = (int) ceil(count($themes) / 2);
            $themes_column1 = array_slice($themes, 0, $half_count);
            $themes_column2 = array_slice($themes, $half_count);
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
            foreach ($themes_column1 as $theme) {
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
            foreach ($themes_column2 as $theme) {
                $themesTableHtml .= self::generateThemeTableRow($theme);
            }
            $themesTableHtml .= '</tbody></table></div></div>';
        } else {
            $themesTableHtml = "No themes found.";
        }
        return $themesTableHtml;
    }
}
