<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: auth-lib.php
 * Description: WordPress Update API
*/



if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_POST["logout"])) {
        session_destroy();
        header("Location: login.php");
        exit();
    } else {
        header('Location: /');
        exit();
    }
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = UtilityHandler::validateUsername($_POST['username']);
    $password = UtilityHandler::validatePassword($_POST['password']);

    // Perform your login authentication logic here
    if ($username === VALID_USERNAME && $password === VALID_PASSWORD) {
        // Successful login
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // Store the User-Agent string
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        session_regenerate_id(true); // Regenerate the session ID
        header('Location: /');
        exit();
    } else {
        // Failed login
        $ip = $_SERVER['REMOTE_ADDR'];

        if (UtilityHandler::isBlacklisted($ip)) {
            // Notify user if IP has been blacklisted
            $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
        } else {
            // Update the number of failed login attempts and check if the IP should be blacklisted
            UtilityHandler::updateFailedAttempts($ip);
            $error = 'Invalid username or password.';
            ErrorHandler::logMessage($error);
            $_SESSION['messages'][] = $error;
        }
    }
}
