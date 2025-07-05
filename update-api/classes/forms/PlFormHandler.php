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
            // Sanitize POST and FILES inputs
            if (isset($_FILES['plugin_file'])) {
                $this->uploadPluginFiles();
            } elseif (isset($_POST['delete_plugin'])) {
                $plugin_name = isset($_POST['plugin_name']) ? Security::sanitizeInput($_POST['plugin_name']) : null;
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
                ? Security::sanitizeInput($_FILES['plugin_file']['name'][$i])
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
                echo '<script>'
                    . 'alert("Error uploading: '
                    . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . '. Only .zip files are allowed.");'
                    . 'window.location.href = "/plupdate";'
                    . '</script>';
                exit;
            }

            $plugin_path = PLUGINS_DIR . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $plugin_path)) {
                echo '<script>'
                    . 'alert("'
                    . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . ' uploaded successfully.");'
                    . 'window.location.href = "/plupdate";'
                    . '</script>';
            } else {
                echo '<script>'
                    . 'alert("Error uploading: '
                    . htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')
                    . '");'
                    . 'window.location.href = "/plupdate";'
                    . '</script>';
            }
        }
    }

    private function deletePlugin(?string $plugin_name): void
    {
        $plugin_name = Security::sanitizeInput($plugin_name);
        $plugin_name = basename($plugin_name);
        $plugin_path = PLUGINS_DIR . '/' . $plugin_name;
        if (
            file_exists($plugin_path)
            && dirname(realpath($plugin_path)) === realpath(PLUGINS_DIR)
        ) {
            if (unlink($plugin_path)) {
                echo '<script>'
                    . 'alert("Plugin deleted successfully!");'
                    . 'window.location.href = "/plupdate";'
                    . '</script>';
            } else {
                echo '<script>'
                    . 'alert("Failed to delete plugin file. Please try again.");'
                    . 'window.location.href = "/plupdate";'
                    . '</script>';
            }
        }
    }
}
