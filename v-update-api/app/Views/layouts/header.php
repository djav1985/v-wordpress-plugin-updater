<?php

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

$currentPath = $requestPath ?? '/';
$navigation = [
    '/home' => 'Manage Hosts',
    '/plupdate' => 'Manage Plugins',
    '/thupdate' => 'Manage Themes',
    '/logs' => 'View Logs',
];
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>API Admin Page</title>
    <link rel="stylesheet" href="/assets/lib/tabler/css/tabler.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet" integrity="sha512-WvVX1YO12zmsvTpUQV8s7ZU98DnkaAokcciMZJfnNWyNzm7//QRV61t4aEr0WdIa4pe854QHLTV302vH92FSMw==" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js" integrity="sha512-oQq8uth41D+gIH/NJvSJvVB85MFk1eWpMK6glnkg6I7EdMqC1XVkW7RxLheXwmFdG03qScCM7gKS/Cx3FYt7Tg==" crossorigin="anonymous"></script>
</head>
<body>
<div class="page">
    <header class="navbar navbar-expand-md d-print-none admin-header">
        <div class="container-xl">
            <a class="logo" href="/home" aria-label="Update API home">
                <img src="/assets/images/logo.png" alt="Vontainment">
            </a>
            <form action="/login" method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Core\SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button class="btn btn-outline-light btn-logout" type="submit" name="logout">Log out</button>
            </form>
        </div>
    </header>
    <div class="admin-nav-wrap">
        <div class="container-xl">
            <nav class="nav nav-tabs admin-nav" aria-label="Admin sections">
                <?php foreach ($navigation as $path => $label) : ?>
                    <a class="nav-link<?php echo $currentPath === $path ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $currentPath === $path ? ' aria-current="page"' : ''; ?>>
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    <div class="page-wrapper">
        <main class="page-body">
            <div class="container-xl">
                <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="false"></div>
