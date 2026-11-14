<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: footer.php
 * Description: WordPress Update API
 */

?>
            </div>
        </main>
        <footer class="footer admin-footer">
            <div class="container-xl">
                <p>&copy; <?php echo date('Y'); ?> Vontainment. All Rights Reserved.</p>
            </div>
        </footer>
    </div>
</div>
<script src="/assets/lib/tabler/js/tabler.min.js"></script>
<script src="/assets/js/header-scripts.js"></script>
<script src="/assets/js/footer-scripts.js"></script>
<?php App\Helpers\MessageHelper::displayAndClearMessages(); ?>
</body>
</html>
