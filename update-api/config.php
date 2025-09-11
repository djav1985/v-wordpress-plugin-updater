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
define('VALID_PASSWORD', 'password');

define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '');

define('SESSION_TIMEOUT_LIMIT', 1800);

define('BASE_DIR', dirname($_SERVER['DOCUMENT_ROOT']));
define('HOSTS_ACL', BASE_DIR);
define('PLUGINS_DIR', BASE_DIR . '/storage/plugins');
define('THEMES_DIR', BASE_DIR . '/storage/themes');
define('LOG_DIR', BASE_DIR . '/storage/logs');
define('DB_FILE', BASE_DIR . '/storage/updater.sqlite');
