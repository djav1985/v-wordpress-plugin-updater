<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: config.php
 * Description: WordPress Update API
 */

define('VALID_USERNAME', 'admin');
define('VALID_PASSWORD', 'password');

define('SESSION_TIMEOUT_LIMIT', 1800);

define('BASE_DIR', dirname($_SERVER['DOCUMENT_ROOT']));
define('HOSTS_ACL', BASE_DIR);
define('PLUGINS_DIR', BASE_DIR . '/storage/plugins');
define('THEMES_DIR', BASE_DIR . '/storage/themes');
define('BLACKLIST_DIR', BASE_DIR . '/storage');
define('LOG_DIR', BASE_DIR . '/storage/logs');
