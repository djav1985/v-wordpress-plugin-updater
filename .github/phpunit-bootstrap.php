<?php
// PHPUnit bootstrap wrapper moved into .github directory to keep repo root clean.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$local = __DIR__ . '/../tests/bootstrap-local.php';
if (file_exists($local)) {
    require_once $local;
}
