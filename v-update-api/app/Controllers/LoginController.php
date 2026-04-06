<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: LoginController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Helpers\ValidationHelper;
use App\Helpers\EncryptionHelper;
use App\Helpers\SessionHelper;
use App\Models\BlacklistModel;
use App\Core\ErrorManager;
use App\Helpers\MessageHelper;
use App\Core\Response;

class LoginController
{
    /**
     * Display the login form when the user is not already authenticated.
     *
     * @return Response
     */
    public function handleRequest(): Response
    {
        if (SessionHelper::get('logged_in') === true) {
            return Response::redirect('/home');
        }
        return Response::view('login');
    }

    /**
     * Handle login form submission and logout actions.
     *
     * @return Response
     */
    public function handleSubmission(): Response
    {
        // Redirect already-logged-in users away from login form
        if (SessionHelper::get('logged_in') === true && !isset($_POST['logout'])) {
            return Response::redirect('/home');
        }

        // Handle logout
        if (SessionHelper::get('logged_in') === true && isset($_POST['logout'])) {
            if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                MessageHelper::addMessage('Invalid CSRF token. Please try again.');
                return Response::redirect('/login');
            }
            return $this->logoutUser();
        }

        // Validate CSRF token
        if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token. Please try again.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return Response::view('login');
        }

        // Trim and validate input
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate credentials
        if ($this->validateCredentials($username, $password)) {
            SessionHelper::set('logged_in', true);
            SessionHelper::set('username', $username);
            SessionHelper::set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            SessionHelper::set('csrf_token', \bin2hex(EncryptionHelper::bytes(32)));
            SessionHelper::set('timeout', time());
            SessionHelper::regenerate();
            return Response::redirect('/home');
        }

        // Handle failed login attempt
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if (!$ip) {
            $error = 'Unable to determine client IP.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return Response::view('login');
        }
        if (BlacklistModel::isBlacklisted($ip)) {
            $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
        } else {
            BlacklistModel::updateFailedAttempts($ip);
            $error = 'Invalid username or password.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
        }

        return Response::view('login');
    }

    /**
     * Validate the supplied login credentials.
     *
     * @param string $username Submitted username
     * @param string $password Submitted password
     * @return bool True if credentials are valid
     */
    private function validateCredentials(string $username, string $password): bool
    {
        $validatedUsername = ValidationHelper::validateUsername($username);
        $validatedPassword = ValidationHelper::validatePassword($password);

        return $validatedUsername === VALID_USERNAME
            && $validatedPassword !== null
            && hash_equals(VALID_PASSWORD, $validatedPassword);
    }

    /**
     * Destroy the session and redirect to login page.
     *
     * @return Response
     */
    private function logoutUser(): Response
    {
        SessionHelper::destroy();
        return Response::redirect('/login');
    }
}
