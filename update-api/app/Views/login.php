<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: login.php
 * Description: WordPress Update API
 */

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
        <img src="/assets/images/logo.png" alt="Logo" class="logo">
        <h2>API Update Admin</h2>
        <form method="post" action="/login">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <label>Username:</label>
            <input type="text" name="username"><br><br>
            <label>Password:</label>
            <input type="password" name="password"><br><br>
            <input type="submit" value="Log In">
        </form>
    </div>
    <?php App\Helpers\MessageHelper::displayAndClearMessages(); ?>
</body>
</html>
