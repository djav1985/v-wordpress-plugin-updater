<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: load-lib.php
 * Description: WordPress Update API
*/

// Validate and sanitize the IP address
$ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);

// Check if the user's IP address is blacklisted
if ($ip && UtilityHandler::isBlacklisted($ip)) {
    http_response_code(403);
    ErrorHandler::logMessage("Blacklisted IP attempted access: $ip", 'error');
    die(1);
} elseif (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if the user is not logged in
    header('Location: login.php');
    die(1);
} elseif (isset($_GET['page'])) {
    // Enforce session timeout and user agent consistency
    $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
    $timeoutExceeded = isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $timeoutLimit);
    $userAgentChanged = isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'];
    if ($timeoutExceeded || $userAgentChanged) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        die(1);
    }

    // List of views that can be loaded. These correspond to the rewrite rules in
    $allowedPages = [
                     'home',
                     'plupdate',
                     'thupdate',
                     'logs',
                    ];

    // Sanitize and validate the requested page
    $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($page && in_array($page, $allowedPages, true)) {
        // Update session timeout
        $_SESSION['timeout'] = time();
        // Authenticated user: load the requested page if it exists.
        $pageFile = dirname(__DIR__) . '/views/' . $page . '.php';
        $pageOutput = $pageFile;
    }
} else {
    http_response_code(404);
    ErrorHandler::logMessage('Invalid page request.', 'warning');
    exit();
}
