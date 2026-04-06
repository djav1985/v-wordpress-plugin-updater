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
use App\Models\BlacklistModel;
use App\Core\ErrorManager;
use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use App\Core\ResponseManager;

class LoginController
{
    public function __construct(
        private SessionManager $session,
        private BlacklistModel $blacklistModel
    ) {
    }

    /**
     * Display the login form when the user is not already authenticated.
     *
     * @return ResponseManager
     */
    public function handleRequest(): ResponseManager
    {
        if ($this->session->get('logged_in') === true) {
            return ResponseManager::redirect('/home');
        }
        return ResponseManager::view('login');
    }

    /**
     * Handle login form submission and logout actions.
     *
     * @return ResponseManager
     */
    public function handleSubmission(): ResponseManager
    {
        // Redirect already-logged-in users away from login form
        if ($this->session->get('logged_in') === true && !isset($_POST['logout'])) {
            return ResponseManager::redirect('/home');
        }

        // Handle logout
        if ($this->session->get('logged_in') === true && isset($_POST['logout'])) {
            if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                MessageHelper::addMessage('Invalid CSRF token. Please try again.');
                return ResponseManager::redirect('/login');
            }
            return $this->logoutUser();
        }

        // Validate CSRF token
        if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token. Please try again.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return ResponseManager::view('login');
        }

        // Trim and validate input
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate credentials
        if ($this->validateCredentials($username, $password)) {
            $this->session->set('logged_in', true);
            $this->session->set('username', $username);
            $this->session->set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $this->session->set('csrf_token', \bin2hex(EncryptionHelper::bytes(32)));
            $this->session->set('timeout', time());
            $this->session->regenerate();
            return ResponseManager::redirect('/home');
        }

        // Handle failed login attempt
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if (!$ip) {
            $error = 'Unable to determine client IP.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return ResponseManager::view('login');
        }
        if ($this->blacklistModel->isBlacklisted($ip)) {
            $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
        } else {
            $this->blacklistModel->updateFailedAttempts($ip);
            $error = 'Invalid username or password.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
        }

        return ResponseManager::view('login');
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
     * @return ResponseManager
     */
    private function logoutUser(): ResponseManager
    {
        $this->session->destroy();
        return ResponseManager::redirect('/login');
    }
}
