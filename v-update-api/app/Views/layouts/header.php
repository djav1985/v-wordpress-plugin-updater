<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: header.php
 * Description: WordPress Update API
 */

?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no" />
    <meta name="robots" content="noindex, nofollow">
    <title>API Admin Page</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js" integrity="sha512-oQq8uth41D+gIH/NJvSJvVB85MFk1eWpMK6glnkg6I7EdMqC1XVkW7RxLheXwmFdG03qScCM7gKS/Cx3FYt7Tg==" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet" integrity="sha512-WvVX1YO12zmsvTpUQV8s7ZU98DnkaAokcciMZJfnNWyNzm7//QRV61t4aEr0WdIa4pe854QHLTV302vH92FSMw==" crossorigin="anonymous" />
    <script src="/assets/js/header-scripts.js"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/mobile.css">
</head>
<body>
<header>
    <div class="logo">
        <a href="/">
            <img src="/assets/images/logo.png" alt="Logo">
        </a>
    </div>
    <div class="logout-button">
        <form action="/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Helpers\SessionHelper::get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <button class="orange-button" type="submit" name="logout">Logout</button>
        </form>
    </div>
</header>
<div class="tab">
    <a href="/home"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/home') {
        echo 'active';
                                        } ?>">Manage Hosts</button></a>
    <a href="/plupdate"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/plupdate') {
        echo 'active';
                                                } ?>">Manage Plugins</button></a>
    <a href="/thupdate"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/thupdate') {
        echo 'active';
                                                } ?>">Manage Themes</button></a>
    <a href="/logs"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/logs') {
        echo 'active';
                                            } ?>">View Logs</button></a>
</div>
<div class="content">
