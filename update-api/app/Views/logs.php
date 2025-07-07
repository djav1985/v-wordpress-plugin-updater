<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: logs.php
 * Description: WordPress Update API
 */

use App\Controllers\LogsController;

require_once __DIR__ . '/layouts/header.php';
$ploutput = LogsController::processLogFile('plugin.log');
$thoutput = LogsController::processLogFile('theme.log');

?>
<div class="content-box">
    <h2>Plugin Log</h2>
    <?php echo $ploutput; ?>
    <h2>Theme Log</h2>
    <?php echo $thoutput; ?>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
