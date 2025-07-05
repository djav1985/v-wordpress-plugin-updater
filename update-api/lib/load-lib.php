<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: load-lib.php
 * Description: WordPress Update API
*/


$ip = $_SERVER['REMOTE_ADDR'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($requestUri, '/');
if ($requestUri === '') {
    $requestUri = '/';
}
$routes = [
           '/'         => 'home.php',
           '/plupdate' => 'plupdate.php',
           '/thupdate' => 'thupdate.php',
           '/logs'     => 'logs.php',
    // Add more routes here as needed
          ];

// Combined blacklist, login logic, redirection, and routing
if (Security::isBlacklisted($ip)) {
    http_response_code(403);
    echo "Your IP address has been blacklisted. If you believe this is an error, please contact us.";
    exit();
} elseif ((!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) && $requestUri !== '/login') {
    header('Location: /login');
    exit();
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $requestUri === '/login') {
    header('Location: /');
    exit();
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (array_key_exists($requestUri, $routes)) {
        $pageFile = dirname(__DIR__) . '/views/' . $routes[$requestUri];
        if (file_exists($pageFile)) {
            $pageOutput = $pageFile;
        }
    }
} else {
    http_response_code(404);
    echo "Page not found or access denied.";
    exit();
}
