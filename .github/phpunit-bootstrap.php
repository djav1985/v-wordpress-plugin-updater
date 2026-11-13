<?php
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
