<?php
/**
 * Simple PSR-4 autoloader for the App namespace.
 */
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/update-api/app/';
    if (!is_dir($baseDir)) {
        // On deployments without the update-api directory
        $baseDir = __DIR__ . '/app/';
    }
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

