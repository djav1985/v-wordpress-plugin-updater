<?php
// Load Composer autoload (root) so WP stubs are available
$roots = [
    __DIR__ . '/../vendor/autoload.php', // repo root
    __DIR__ . '/vendor/autoload.php',    // fallback
];
foreach ($roots as $f) {
    if (is_file($f)) { require $f; break; }
}

// Also load app autoload for update-api if present
$appAutoload = __DIR__ . '/../update-api/vendor/autoload.php';
if (is_file($appAutoload)) {
    require $appAutoload;
}

// Define project constants so PHPStan stops complaining
if (!defined('VONTMENT_KEY')) { define('VONTMENT_KEY', ''); }
if (!defined('VONTMENT_PLUGINS')) { define('VONTMENT_PLUGINS', ''); }
if (!defined('VONTMENT_THEMES')) { define('VONTMENT_THEMES', ''); }
