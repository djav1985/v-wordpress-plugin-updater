
<?php
/*
* Project: Update API
* Author: Vontainment
* URL: https://vontainment.com
* File: plupdate-form.php
* Description: WordPress Update API with file upload and deletion functionality
*/

// Handle plugin file uploads
if (isset($_FILES['plugin_file'])) {
    $allowed_extensions = ['zip'];
    $total_files = count($_FILES['plugin_file']['name']);

    for ($i = 0; $i < $total_files; $i++) {
        $file_name = $_FILES['plugin_file']['name'][$i];
        $file_tmp = $_FILES['plugin_file']['tmp_name'][$i];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Check if the uploaded file has no errors and has an allowed extension
        if ($_FILES['plugin_file']['error'][$i] !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
            exit;
        }

        $plugin_slug = explode("_", $file_name)[0];
        $existing_plugins = glob(PLUGINS_DIR . '/' . $plugin_slug . '_*');

        // Remove existing plugins with the same slug
        foreach ($existing_plugins as $plugin) {
            if (is_file($plugin)) {
                unlink($plugin);
            }
        }

        $plugin_path = PLUGINS_DIR . '/' . $file_name;
        move_uploaded_file($file_tmp, $plugin_path);
    }
}

// Check if a plugin was deleted
if (isset($_POST['delete_plugin'])) {
    $plugin_name = $_POST['plugin_name'];
    $plugin_path = PLUGINS_DIR . '/' . $plugin_name;

    if (file_exists($plugin_path)) {
        if (unlink($plugin_path)) {
            echo '<script>
                alert("Plugin deleted successfully!");
                window.location.href = "/plupdate";
            </script>';
        } else {
            echo '<script>
                alert("Failed to delete plugin file. Please try again.");
                window.location.href = "/plupdate";
            </script>';
        }
    }
}

// Handle plugin downloads
if (isset($_GET['download_plugin'])) {
    $file_name = $_GET['download_plugin'];
    $file_path = PLUGINS_DIR . '/' . $file_name;

    if (file_exists($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . basename($file_path));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        flush(); // Flush system output buffer
        readfile($file_path);
        exit;
    } else {
        http_response_code(404);
        echo "File not found.";
        exit;
    }
}
