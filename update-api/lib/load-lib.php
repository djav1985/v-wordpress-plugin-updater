<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: load-lib.php
 * Description: WordPress Update API
*/


$ip = $_SERVER['REMOTE_ADDR'];

// List of views that can be loaded. These correspond to the rewrite rules in
// `.htaccess`.
$allowedPages = [
    'home',
    'plupdate',
    'thupdate',
    'logs',
    // Add more page names here as needed
];

// Sanitize and validate the requested page against the whitelist
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS);
if (!$page || !in_array($page, $allowedPages, true)) {
    ErrorHandler::logMessage('Invalid page request: ' . $page, 'warning');
    $page = null;
}

// Verify that the User-Agent matches the one used during login
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (!isset($_SESSION['user_agent']) || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header('Location: /login');
        exit();
    }
}

// Combined blacklist, login logic, redirection, and page loading.
if (SecurityHandler::isBlacklisted($ip)) {
    http_response_code(403);
    $error = 'Your IP address has been blacklisted. If you believe this is an error, please contact us.';
    ErrorHandler::logMessage($error);
    $_SESSION['messages'][] = $error;
    echo $error;
    exit();
} elseif (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Any unauthenticated request to index.php should be sent to the login page.
    header('Location: /login');
    exit();
} else {
    // Authenticated user: load the requested page if it exists.
    if ($page !== null) {
        $pageFile = dirname(__DIR__) . '/views/' . $page . '.php';
        if (file_exists($pageFile)) {
            $pageOutput = $pageFile;
        } else {
            http_response_code(404);
            $error = 'Page not found.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
            echo $error;
            exit();
        }
    } else {
        http_response_code(404);
        $error = 'Page not found or access denied.';
        ErrorHandler::logMessage($error);
        $_SESSION['messages'][] = $error;
        echo $error;
        exit();
    }
}
