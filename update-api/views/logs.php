<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: logs.php
 * Description: WordPress Update API
*/


$ploutput = LogsHelper::processLogFile('plugin.log');
$thoutput = LogsHelper::processLogFile('theme.log');

?>
<div class="content-box">
    <h2>Plugin Log</h2>
    <?php echo $ploutput; ?>
    <h2>Theme Log</h2>
    <?php echo $thoutput; ?>
</div>
