<?php
/*
WP Plugin Update API
Version: 1.1
Author: Vontainment
Author URI: https://vontainment.com
*/

// Check if the user is logged in
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Display the content for logged in users
?>

<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Page</title>
    <link rel="stylesheet" href="./static/css/index.css">
</head>

<body>
    <header>
        <img src="./static/img/logo.png" alt="Lego" width="300px" height="60px">
        <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
    </header>
    <div class="section">
        <h2>Hosts</h2>
        <?php
        // Check if an entry was updated
        if (isset($_POST['update'])) {
            $hosts_file = './HOSTS';
            $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
            $line_number = $_POST['id'];
            $domain = $_POST['domain'];
            $key = $_POST['key'];
            $entries[$line_number] = $domain . ' ' . $key;
            file_put_contents($hosts_file, implode("\n", $entries) . "\n");
        }

        // Check if an entry was deleted
        if (isset($_POST['delete'])) {
            $hosts_file = './HOSTS';
            $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
            $line_number = $_POST['id'];
            unset($entries[$line_number]);
            file_put_contents($hosts_file, implode("\n", $entries) . "\n");
        }

        // Check if a new entry was added
        if (isset($_POST['add'])) {
            $hosts_file = './HOSTS';
            $domain = $_POST['domain'];
            $key = $_POST['key'];
            $new_entry = $domain . ' ' . $key;
            file_put_contents($hosts_file, $new_entry . "\n", FILE_APPEND);
        }

        // Display the table of entries
        $hosts_file = './HOSTS';
        $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
        ?>
        <div class="row">
            <div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Key</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($entries as $line_number => $entry) {
                            $fields = explode(' ', $entry);
                            $domain = $fields[0];
                            $key = $fields[1];
                            if ($i % 2 == 0) {
                        ?>
                                <tr>
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?php echo $line_number; ?>">
                                        <td><input type="text" name="domain" value="<?php echo $domain; ?>"></td>
                                        <td><input type="text" name="key" value="<?php echo $key; ?>"></td>
                                        <td>
                                            <input type="submit" name="update" value="Update">
                                            <input type="submit" name="delete" value="Delete">
                                        </td>
                                    </form>
                                </tr>
                        <?php
                            }
                            $i++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="column">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Key</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($entries as $line_number => $entry) {
                            $fields = explode(' ', $entry);
                            $domain = $fields[0];
                            $key = $fields[1];
                            if ($i % 2 != 0) {
                        ?>
                                <tr>
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?php echo $line_number; ?>">
                                        <td><input type="text" name="domain" value="<?php echo $domain; ?>"></td>
                                        <td><input type="text" name="key" value="<?php echo $key; ?>"></td>
                                        <td>
                                            <input type="submit" name="update" value="Update">
                                            <input type="submit" name="delete" value="Delete">
                                        </td>
                                    </form>
                                </tr>
                        <?php
                            }
                            $i++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section">
            <h2>Add Entry</h2>
            <form method="post">
                <div class="form-group">
                    <label for="domain">Domain:</label>
                    <input type="text" name="domain" id="domain" required>
                </div>
                <div class="form-group">
                    <label for="key">Key:</label>
                    <input type="text" name="key" id="key" required>
                </div>
                <div class="form-group">
                    <input type="submit" name="add" value="Add Entry">
                </div>
            </form>
        </div>
    </div>
    <div class="section">
        <h2>Plugins</h2>
        <div id="plugins-table-wrapper">
            <?php
            // Include the code to generate the table
            include('plugins_table.php');
            ?>
        </div>
        <script>
            function updatePluginsTable() {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("plugins-table-wrapper").innerHTML = this.responseText;
                    }
                };
                xmlhttp.open("GET", "plugins_table.php", true);
                xmlhttp.send();
            }
        </script>

        <div class="section">
            <h2>Upload Plugin</h2>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_file'])) {
                $allowed_extensions = array('zip');
                $upload_file = $_FILES['plugin_file'];

                $upload_dir = './plugins/';

                $file_name = basename($upload_file['name']);
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_extension, $allowed_extensions)) {
                    echo '<p class="error">Invalid file format. Please upload a ZIP file.</p>';
                } else {
                    $file_path = $upload_dir . $file_name;

                    if (file_exists($file_path)) {
                        echo '<p class="error">File already exists.</p>';
                    } else {
                        if (move_uploaded_file($upload_file['tmp_name'], $file_path)) {
                            echo '<p class="success">File uploaded successfully.</p>';
                            // Call the function to update the plugins table
                            echo '<script>updatePluginsTable();</script>';
                        } else {
                            echo '<p class="error">Error uploading file.</p>';
                        }
                    }
                }
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plugin'])) {
                $plugin_name = $_POST['plugin_name'];
                $plugin_path = './plugins/' . $plugin_name;

                if (file_exists($plugin_path)) {
                    unlink($plugin_path);
                    echo '<p class="success">Plugin deleted successfully.</p>';
                    // Call the function to update the plugins table
                    echo '<script>updatePluginsTable();</script>';
                } else {
                    echo '<p class="error">Plugin not found.</p>';
                }
            }
            ?>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="plugin_file">
                <input type="submit" name="upload_plugin" value="Upload">
            </form>
        </div>
    </div>

</body>

</html>