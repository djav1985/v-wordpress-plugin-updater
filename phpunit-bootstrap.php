<?php
// PHPUnit bootstrap wrapper — include Composer autoload then local test stubs.
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

$local = __DIR__ . '/tests/bootstrap-local.php';
if (file_exists($local)) {
    require_once $local;
}
