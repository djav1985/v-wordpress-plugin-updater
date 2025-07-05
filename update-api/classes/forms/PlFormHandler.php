<?php
// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: PluginUpdateFormHandler.php
 * Description: WordPress Update API
 */



class PlFormHandler
{
    public function handleRequest(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['csrf_token'], $_SESSION['csrf_token'])
            && $_POST['csrf_token'] === $_SESSION['csrf_token']
        ) {
            // Validate POST and FILES inputs
            if (isset($_FILES['plugin_file'])) {
                $this->uploadPluginFiles();
            } elseif (isset($_POST['delete_plugin'])) {
                $plugin_name = isset($_POST['plugin_name']) ? SecurityHandler::validateSlug($_POST['plugin_name']) : null;
                $this->deletePlugin($plugin_name);
            } else {
                die('Invalid form action.');
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            die('Invalid CSRF token.');
        }
    }

    private function uploadPluginFiles(): void
    {
        $allowed_extensions = ['zip'];
        $total_files = count($_FILES['plugin_file']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $file_name = isset($_FILES['plugin_file']['name'][$i])
                ? SecurityHandler::validateFilename($_FILES['plugin_file']['name'][$i])
                : '';
            $file_tmp = isset($_FILES['plugin_file']['tmp_name'][$i])
                ? $_FILES['plugin_file']['tmp_name'][$i]
                : '';
            $file_size = isset($_FILES['plugin_file']['size'][$i])
                ? filter_var($_FILES['plugin_file']['size'][$i], FILTER_VALIDATE_INT)
                : 0;
            $file_error = isset($_FILES['plugin_file']['error'][$i])
                ? filter_var($_FILES['plugin_file']['error'][$i], FILTER_VALIDATE_INT)
                : UPLOAD_ERR_NO_FILE;
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $plugin_slug = explode("_", $file_name)[0];
            $existing_plugins = glob(PLUGINS_DIR . '/' . $plugin_slug . '_*');
            foreach ($existing_plugins as $plugin) {
                if (is_file($plugin)) {
                    unlink($plugin);
                }
            }

            if ($file_error !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
                $_SESSION['messages'][] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . '. Only .zip files are allowed.';
                header('Location: /plupdate');
                exit();
            }

            $plugin_path = PLUGINS_DIR . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $plugin_path)) {
                $_SESSION['messages'][] = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') . ' uploaded successfully.';
            } else {
                $_SESSION['messages'][] = 'Error uploading: ' . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
            }
            header('Location: /plupdate');
            exit();
        }
    }

    private function deletePlugin(?string $plugin_name): void
    {
        $plugin_name = SecurityHandler::validateFilename($plugin_name);
        $plugin_name = basename((string) $plugin_name);
        $plugin_path = PLUGINS_DIR . '/' . $plugin_name;
        if (
            file_exists($plugin_path)
            && dirname(realpath($plugin_path)) === realpath(PLUGINS_DIR)
        ) {
            if (unlink($plugin_path)) {
                $_SESSION['messages'][] = 'Plugin deleted successfully!';
            } else {
                $_SESSION['messages'][] = 'Failed to delete plugin file. Please try again.';
            }
            header('Location: /plupdate');
            exit();
        }
    }
}
