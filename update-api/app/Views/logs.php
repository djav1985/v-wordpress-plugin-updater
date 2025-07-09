<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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

require_once __DIR__ . '/layouts/header.php';

?>
<div class="content-box">
    <h2>Plugin Log</h2>
    <?php echo $ploutput; ?>
    <h2>Theme Log</h2>
    <?php echo $thoutput; ?>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
