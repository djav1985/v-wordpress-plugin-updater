<?php

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 * @version 3.0.0
 *
 * File: ClassLoader.php
 * Description: WordPress Update API
 */

namespace App\Lib;

use App\Core\ErrorHandler;

// Autoload function to include class files without namespaces
spl_autoload_register(function ($class_name) {
    $base = dirname(__DIR__) . '/Controllers/';
    $target = strtolower($class_name);
    foreach (glob($base . '*.php') as $file) {
        if (strtolower(basename($file, '.php')) === $target) {
            require_once $file;
            return;
        }
    }
    ErrorHandler::logMessage('Class file not found: ' . $class_name);
});
