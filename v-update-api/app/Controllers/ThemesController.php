<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: ThemesController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\SessionManager;
use App\Helpers\ValidationHelper;
use App\Core\ErrorManager;
use App\Models\ThemeModel;
use App\Helpers\MessageHelper;
use App\Core\Response;
use App\Helpers\RequestInput;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class ThemesController
{
    /**
     * Handles GET requests for theme-related actions.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $themesTableHtml = $this->getThemesTableHtml();
        return Response::view('thupdate', [
            'themesTableHtml' => $themesTableHtml,
        ]);
    }

    /**
     * Handles POST submissions for theme-related actions.
     *
    * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $post = $request->getParsedBody();
        if (!is_array($post)) {
            return new Response(400);
        }
        $token = RequestInput::string($post, 'csrf_token') ?? '';
        if (!ValidationHelper::validateCsrfToken($token)) {
            $error = 'Invalid Form Action.';
            ErrorManager::log($error);
            $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
            if ($isAjax) {
                return Response::text($error, 400);
            }
            MessageHelper::addMessage($error);
            return Response::redirect('/thupdate');
        }

        $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
        $uploadedFiles = $request->getUploadedFiles();
        if (isset($uploadedFiles['theme_file'])) {
            $upload = $uploadedFiles['theme_file'];
            if (!is_array($upload) && !$upload instanceof UploadedFileInterface) {
                return Response::text('Invalid upload payload.', 400);
            }
            $messages = ThemeModel::uploadPsrFiles($upload, $isAjax);
            if ($isAjax) {
                return Response::text(implode("\n", $messages));
            }
            foreach ($messages as $message) {
                MessageHelper::addMessage($message);
            }
            return Response::redirect('/thupdate');
        } elseif (isset($post['delete_theme'])) {
            $themeName = ValidationHelper::validateFilename(
                RequestInput::string($post, 'theme_name') ?? ''
            );
            if ($themeName !== null && ThemeModel::deleteTheme($themeName)) {
                MessageHelper::addMessage('Theme deleted successfully!');
            } else {
                $error = 'Failed to delete theme file. Please try again.';
                ErrorManager::log($error);
                MessageHelper::addMessage($error);
            }
            return Response::redirect('/thupdate');
        }
        return Response::redirect('/thupdate');
    }

    /**
     * Generates an HTML table row for a theme.
     * @param array{slug: string, version: string} $theme
     */
    private function generateThemeTableRow(array $theme): string
    {
        $name = str_replace(['-', '_'], ' ', $theme['slug']);
        $version = $theme['version'];
        $themeFile = $theme['slug'] . '_' . $version . '.zip';
        $csrfToken = SessionManager::getInstance()->get('csrf_token') ?? '';
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
     *
     * @return string HTML table or message.
     */
    private function getThemesTableHtml(): string
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
                $themesTableHtml .= $this->generateThemeTableRow($theme);
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
                $themesTableHtml .= $this->generateThemeTableRow($theme);
            }
            $themesTableHtml .= '</tbody></table></div></div>';
        } else {
            $themesTableHtml = "No themes found.";
        }
        return $themesTableHtml;
    }
}
