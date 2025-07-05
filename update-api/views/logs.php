<?php

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 * @version 3.0.0
 *
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
