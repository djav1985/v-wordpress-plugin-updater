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
    <?php
    /** @var string $ploutput */
    $ploutput = $ploutput ?? '';
    echo $ploutput;
    ?>
    <h2>Theme Log</h2>
    <?php
    /** @var string $thoutput */
    $thoutput = $thoutput ?? '';
    echo $thoutput;
    ?>
    <form method="post" action="/logs" style="margin-top:20px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <button class="red-button" type="submit" name="clear_logs">Clear Logs</button>
    </form>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
