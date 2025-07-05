<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: autoload.php
 * Description: WordPress Update API
 */

// Autoload function to include class files without namespaces
spl_autoload_register(function ($class_name) {
    $base = dirname(__DIR__) . '/classes/';
    $file = $base . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    $file = $base . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    error_log('Class file not found: ' . $class_name);
});
