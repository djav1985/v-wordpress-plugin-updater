<?php
// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ThemeUpdateFormHandler.php
 * Description: WordPress Update API
 */



class ThFormHandler
{
    public function handleRequest(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['csrf_token'], $_SESSION['csrf_token'])
            && $_POST['csrf_token'] === $_SESSION['csrf_token']
        ) {
            if (isset($_FILES['theme_file'])) {
                $this->uploadThemeFiles();
            } elseif (isset($_POST['delete_theme'])) {
                $theme_name = isset($_POST['theme_name']) ? SecurityHandler::validateSlug($_POST['theme_name']) : null;
                $this->deleteTheme($theme_name);
            } else {
                die('Invalid form action.');
            }
        } else {
            die('Invalid CSRF token.');
        }
    }

    private function uploadThemeFiles(): void
    {
        $allowed_extensions = ['zip'];
        $total_files = count($_FILES['theme_file']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($_FILES['theme_file']['name'][$i])
                ? SecurityHandler::validateFilename($_FILES['theme_file']['name'][$i])
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
                $_SESSION['messages'][] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . '. Only .zip files are allowed.';
                header('Location: /thupdate');
                exit();
            }

            $theme_path = THEMES_DIR . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $theme_path)) {
                $_SESSION['messages'][] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
            } else {
                $_SESSION['messages'][] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
            }
            header('Location: /thupdate');
            exit();
        }
    }

    private function deleteTheme(?string $theme_name): void
    {
        $theme_name = SecurityHandler::validateFilename($theme_name);
        $theme_name = basename((string) $theme_name);
        $theme_path = THEMES_DIR . '/' . $theme_name;
        if (
            file_exists($theme_path)
            && dirname(realpath($theme_path)) === realpath(THEMES_DIR)
        ) {
            if (unlink($theme_path)) {
                $_SESSION['messages'][] = 'Theme deleted successfully!';
            } else {
                $_SESSION['messages'][] = 'Failed to delete theme file. Please try again.';
            }
            header('Location: /thupdate');
            exit();
        }
    }
}
