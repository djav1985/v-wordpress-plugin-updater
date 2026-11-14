<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: login.php
 * Description: WordPress Update API
 */

$csrfToken = \App\Core\SessionManager::getInstance()->get('csrf_token') ?? '';
$escapedCsrfToken = htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>API Update Admin Login</title>
    <link rel="stylesheet" href="/assets/lib/tabler/css/tabler.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body class="login-page">
        <div
            id="toast-container"
            class="toast-container position-fixed bottom-0 end-0 p-3"
            aria-live="polite"
            aria-atomic="false"
        ></div>
    <main class="login-shell">
        <section class="card login-card">
            <div class="card-body">
                <a href="/" class="login-logo" aria-label="Vontainment home">
                    <img src="/assets/images/logo.png" alt="Vontainment">
                </a>
                <div class="page-pretitle text-center">WordPress Update API</div>
                <h1 class="login-title">Admin sign in</h1>
                <form method="post" action="/login">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo $escapedCsrfToken; ?>"
                    >
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                    <input
                        class="form-control"
                        id="username"
                        type="text"
                        name="username"
                        autocomplete="username"
                        required
                        autofocus
                    >
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                    <input
                        class="form-control"
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                </form>
            </div>
        </section>
    </main>
    <script src="/assets/lib/tabler/js/tabler.min.js"></script>
    <script src="<?php echo \App\Helpers\AssetHelper::url('/assets/js/header-scripts.js'); ?>"></script>
    <?php App\Helpers\MessageHelper::displayAndClearMessages(); ?>
</body>
</html>
