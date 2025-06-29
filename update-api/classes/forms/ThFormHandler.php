<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ThemeUpdateFormHandler.php
 * Description: WordPress Update API
 */

namespace UpdateApi\forms;

use UpdateApi\util\Security;

class ThFormHandler
{
    public function handleRequest()
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['csrf_token'], $_SESSION['csrf_token'])
            && $_POST['csrf_token'] === $_SESSION['csrf_token']
        ) {
            if (isset($_FILES['theme_file'])) {
                $this->uploadThemeFiles();
            } elseif (isset($_POST['delete_theme'])) {
                $theme_name = isset($_POST['theme_name']) ? Security::sanitizeInput($_POST['theme_name']) : null;
                $this->deleteTheme($theme_name);
            } else {
                die('Invalid form action.');
            }
        } else {
            die('Invalid CSRF token.');
        }
    }

    private function uploadThemeFiles()
    {
        $allowed_extensions = ['zip'];
        $total_files = count($_FILES['theme_file']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($_FILES['theme_file']['name'][$i])
                ? Security::sanitizeInput($_FILES['theme_file']['name'][$i])
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
            $theme_slug = explode("_", $file_name)[0];
            $existing_themes = glob(THEMES_DIR . '/' . $theme_slug . '_*');
            foreach ($existing_themes as $theme) {
                if (is_file($theme)) {
                    unlink($theme);
                }
            }

            if ($file_error !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
                echo '<script>'
                    . 'alert("Error uploading: '
                    . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . '. Only .zip files are allowed.");'
                    . 'window.location.href = "/thupdate";'
                    . '</script>';
                exit;
            }

            $theme_path = THEMES_DIR . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $theme_path)) {
                echo '<script>'
                    . 'alert("'
                    . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . ' uploaded successfully.");'
                    . 'window.location.href = "/thupdate";'
                    . '</script>';
            } else {
                echo '<script>'
                    . 'alert("Error uploading: '
                    . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . '");'
                    . 'window.location.href = "/thupdate";'
                    . '</script>';
            }
        }
    }

    private function deleteTheme($theme_name)
    {
        $theme_name = Security::sanitizeInput($theme_name);
        $theme_path = THEMES_DIR . '/' . $theme_name;
        if (file_exists($theme_path)) {
            if (unlink($theme_path)) {
                echo '<script>'
                    . 'alert("Theme deleted successfully!");'
                    . 'window.location.href = "/thupdate";'
                    . '</script>';
            } else {
                echo '<script>'
                    . 'alert("Failed to delete theme file. Please try again.");'
                    . 'window.location.href = "/thupdate";'
                    . '</script>';
            }
        }
    }
}
