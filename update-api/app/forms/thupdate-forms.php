
<?php
/*
* Project: Update API
* Author: Vontainment
* URL: https://vontainment.com
* File: thupdate-form.php
* Description: WordPress Theme Update API with file upload and deletion functionality
*/

// Handle theme file uploads
if (isset($_FILES['theme_file'])) {
    $allowed_extensions = ['zip'];
    $total_files = count($_FILES['theme_file']['name']);

    // Loop through each uploaded theme file
    for ($i = 0; $i < $total_files; $i++) {
        $file_name = $_FILES['theme_file']['name'][$i];
        $file_tmp = $_FILES['theme_file']['tmp_name'][$i];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Check if the uploaded file has no errors and has an allowed extension
        if ($_FILES['theme_file']['error'][$i] !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions)) {
            exit;
        }

        $theme_slug = explode("_", $file_name)[0];
        $existing_themes = glob(THEMES_DIR . '/' . $theme_slug . '_*');

        // Remove existing themes with the same slug
        foreach ($existing_themes as $theme) {
            if (is_file($theme)) {
                unlink($theme);
            }
        }

        $theme_path = THEMES_DIR . '/' . $file_name;
        move_uploaded_file($file_tmp, $theme_path);
    }
}

// Check if a theme was deleted
if (isset($_POST['delete_theme'])) {
    $theme_name = $_POST['theme_name'];
    $theme_path = THEMES_DIR . '/' . $theme_name;

    if (file_exists($theme_path)) {
        if (unlink($theme_path)) {
            echo '<script>
                alert("Theme deleted successfully!");
                window.location.href = "/thupdate";
            </script>';
        } else {
            echo '<script>
                alert("Failed to delete theme file. Please try again.");
                window.location.href = "/thupdate";
            </script>';
        }
    }
}

// Handle theme downloads
if (isset($_GET['download_theme'])) {
    $file_name = $_GET['download_theme'];
    $file_path = THEMES_DIR . '/' . $file_name;

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
