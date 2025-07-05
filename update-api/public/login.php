<?php

/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 * @version 3.0.0
 *
 * File: login.php
 * Description: WordPress Update API
 */

// Set secure session cookie parameters before starting the session
$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params(
    [
     'httponly' => true,
     'secure'   => $secureFlag,
     'samesite' => 'Lax',
    ]
);
session_start();
require_once '../config.php';
require_once '../lib/class-lib.php';
require_once '../lib/auth-lib.php';
?>

<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>API Update Admin Login</title>
    <script src="/assets/js/header-scripts.js"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/mobile.css">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>

<body>
    <div class="login-box">
        <img src="assets/images/logo.png" alt="Logo" class="logo">
        <h2>API Update Admin</h2>
        <form method="post" action="/login">
            <label>Username:</label>
            <input type="text" name="username"><br><br>
            <label>Password:</label>
            <input type="password" name="password"><br><br>
            <input type="submit" value="Log In">
        </form>
    </div>
    <?php echo ErrorHandler::displayAndClearMessages(); ?>
</body>

</html>
