<?php
/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: auth-lib.php
 * Description: WordPress Update API
 * /lib/auth-lib.php
 */


if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_POST["logout"])) {
        session_destroy();
        header("Location: login.php");
        exit;
    } else {
        header('Location: /home');
        exit;
    }
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    // Perform your login authentication logic here
    if ($username === VALID_USERNAME && $password === VALID_PASSWORD) {
        // Successful login
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['timeout'] = time();  // Set the session timeout time
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // Store the User-Agent string
        session_regenerate_id(true); // Regenerate the session ID
        // Generate and store CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure CSRF token

        header('Location: /home');
        exit;
    } else {
        // Failed login
        $ip = $_SERVER['REMOTE_ADDR'];

        if (is_blacklisted($ip)) {
            // User is blacklisted
            $error_msg = "Your IP has been blacklisted due to multiple failed login attempts.";
            $_SESSION['error'] = $error_msg;
        } else {
            // Update the number of failed login attempts
            update_failed_attempts($ip);
            $error_msg = "Invalid username or password.";
            $_SESSION['error'] = $error_msg;
        }

        header("Location: login.php");
        exit;
    }
}
