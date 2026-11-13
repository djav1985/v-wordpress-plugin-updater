<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: phpunit-bootstrap.php
 * Description: Loads application autoloaders and test support.
 */

// PHPUnit bootstrap wrapper moved into .github directory to keep repo root clean.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$updateApiAutoload = __DIR__ . '/../v-update-api/vendor/autoload.php';
if (file_exists($updateApiAutoload)) {
    require_once $updateApiAutoload;
}

$pluginAutoload = __DIR__ . '/../v-wp-updater/vendor/autoload.php';
if (file_exists($pluginAutoload)) {
    require_once $pluginAutoload;
}

$local = __DIR__ . '/../tests/bootstrap-local.php';
if (file_exists($local)) {
    require_once $local;
}

require_once __DIR__ . '/../tests/Support/SharedRequestFactory.php';
