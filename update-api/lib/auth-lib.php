<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: auth-lib.php
 * Description: WordPress Update API
*/


use UpdateApi\util\Security;

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
    $username = Security::sanitizeInput($_POST['username']);
    $password = Security::sanitizeInput($_POST['password']);

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

        if (Security::isBlacklisted($ip)) {
            // Show the message that the user has been blacklisted
            $error_msg = "Your IP has been blacklisted due to multiple failed login attempts.";
        } else {
            // Update the number of failed login attempts and check if the IP should be blacklisted
            Security::updateFailedAttempts($ip);
            $error_msg = "Invalid username or password.";
        }
    }
}
