<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: class-lib.php
 * Description: WordPress Update API
 */

// Autoload function to include class files without namespaces
spl_autoload_register(function ($class_name) {
    $base = dirname(__DIR__) . '/classes/';
    $target = strtolower($class_name);
    foreach (glob($base . '*.php') as $file) {
        if (strtolower(basename($file, '.php')) === $target) {
            require_once $file;
            return;
        }
    }
    error_log('Class file not found: ' . $class_name);
});
