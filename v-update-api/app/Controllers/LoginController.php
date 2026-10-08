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

use App\Core\SessionManager;
use App\Helpers\ValidationHelper;
use App\Helpers\EncryptionHelper;
use App\Models\BlacklistModel;
use App\Core\ErrorManager;
use App\Helpers\MessageHelper;
use App\Core\ResponseManager;

class LoginController
{
    /**
     * Display the login form when the user is not already authenticated.
     *
     * @return ResponseManager
     */
    public function handleRequest(): ResponseManager
    {
        if (SessionManager::getInstance()->get('logged_in') === true) {
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
        if (SessionManager::getInstance()->get('logged_in') === true && !isset($_POST['logout'])) {
            return ResponseManager::redirect('/home');
        }

        // Handle logout
        if (SessionManager::getInstance()->get('logged_in') === true && isset($_POST['logout'])) {
            return $this->logoutUser();
        }

        // Trim and validate input
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate credentials
        if ($this->validateCredentials($username, $password)) {
            SessionManager::getInstance()->set('logged_in', true);
            SessionManager::getInstance()->set('username', $username);
            SessionManager::getInstance()->set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            SessionManager::getInstance()->set('csrf_token', \bin2hex(EncryptionHelper::bytes(32)));
            SessionManager::getInstance()->set('timeout', time());
            SessionManager::getInstance()->regenerate();
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

        BlacklistModel::updateFailedAttempts($ip);
        $error = 'Invalid username or password.';
        ErrorManager::log($error);
        MessageHelper::addMessage($error);

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
        SessionManager::getInstance()->destroy();
        return ResponseManager::redirect('/login');
    }
}
