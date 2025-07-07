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

require_once __DIR__ . '/layouts/header.php';

?>
<div class="content-box">
    <h2>Plugin Log</h2>
    <?php echo $pluginLog ?? ''; ?>
    <h2>Theme Log</h2>
    <?php echo $themeLog ?? ''; ?>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
