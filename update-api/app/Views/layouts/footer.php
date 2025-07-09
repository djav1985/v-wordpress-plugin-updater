<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: footer.php
 * Description: WordPress Update API
 */

?>
</div>
<footer>
    <p>&copy; <?php echo date("Y"); ?> Vontainment. All Rights Reserved.</p>
</footer>
<script src="/assets/js/footer-scripts.js"></script>
<?php echo App\Core\ErrorMiddleware::displayAndClearMessages(); ?>
</body>
</html>
