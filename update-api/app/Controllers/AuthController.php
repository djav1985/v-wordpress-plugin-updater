<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: AuthController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Utility;
use App\Core\ErrorMiddleware;

class AuthController extends Controller
{
    public static function handleRequest(): void
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
                session_destroy();
                header('Location: /login');
                exit();
            }
            header('Location: /home');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) ? Utility::validateUsername($_POST['username']) : null;
            $password = isset($_POST['password']) ? Utility::validatePassword($_POST['password']) : null;

            if ($username === VALID_USERNAME && $password === VALID_PASSWORD) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                session_regenerate_id(true);
                header('Location: /home');
                exit();
            }

            $ip = $_SERVER['REMOTE_ADDR'];
            if (Utility::isBlacklisted($ip)) {
                $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            } else {
                Utility::updateFailedAttempts($ip);
                $error = 'Invalid username or password.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
        }

        // Use the render method to include the login view
        (new self())->render('login', []);
    }
}
