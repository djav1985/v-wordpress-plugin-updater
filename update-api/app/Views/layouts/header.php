<?php
/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.0/min/dropzone.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.7.0/min/dropzone.min.css" rel="stylesheet" />
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
            <button class="orange-button" type="submit" name="logout">Logout</button>
        </form>
    </div>
</header>
<div class="tab">
    <a href="/"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/') {
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
