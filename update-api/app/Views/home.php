<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: home.php
 * Description: WordPress Update API
 */

use App\Controllers\HomeController;

require_once __DIR__ . '/layouts/header.php';
HomeController::handleRequest();
$hostsTableHtml = HomeController::getHostsTableHtml();
?>

<div class="content-box">
    <h2>Allowed Hosts</h2>
    <div id="hosts_table">
        <?php echo $hostsTableHtml; ?>
    </div>
    <div class="home section">
        <h2>Add Entry</h2>
        <form class="entry-form" method="post" action="/home">
            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <div class="form-group">
                <label for="domain">Domain:</label>
                <input type="text" name="domain" id="domain" required>
            </div>
            <div class="form-group">
                <label for="key">Key:</label>
                <input type="text" name="key" id="key" required>
            </div>
            <div class="form-group">
                <input type="submit" name="add_entry" value="Add Entry">
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
