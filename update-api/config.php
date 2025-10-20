<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: config.php
 * Description: WordPress Update API
 */

define('VALID_USERNAME', 'admin');
define('VALID_PASSWORD_HASH', '$2y$10$tYi5dWtBVRNkLqoSwV0yfuzM9Wh6A7O6oDulEGaM1lM3FsIaVvQ9e');

define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '98aafe518ece74e33d92a2e7c4833dd4632afc208f1a978ed13b2b59d74e9af3');

define('SESSION_TIMEOUT_LIMIT', 1800);

define('BASE_DIR', dirname($_SERVER['DOCUMENT_ROOT']));
define('PLUGINS_DIR', BASE_DIR . '/storage/plugins');
define('THEMES_DIR', BASE_DIR . '/storage/themes');
define('LOG_DIR', BASE_DIR . '/storage/logs');
define('LOG_FILE', LOG_DIR . '/app.log');
define('DB_FILE', BASE_DIR . '/storage/updater.sqlite');
