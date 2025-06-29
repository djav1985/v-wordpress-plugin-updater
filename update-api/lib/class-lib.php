<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: autoload.php
 * Description: WordPress Update API
 */

// Autoload function to automatically include class files
spl_autoload_register(function ($class_name) {
    $parts = explode('\\', $class_name);
    // Remove the first part of the namespace (e.g., 'Vontainment')
    array_shift($parts);
    $file = dirname(__DIR__) . '/classes/' . implode('/', $parts) . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Class file not found: " . $file);
    }
});
