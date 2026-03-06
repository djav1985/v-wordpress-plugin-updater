<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: LoginController.php
 * Description: WordPress Update API
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\ValidationHelper;
use App\Models\BlacklistModel;
use App\Core\ErrorManager;
use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use App\Core\Response;

class LoginController extends Controller
{
    /**
     * Display the login form when the user is not already authenticated.
     *
     * @return Response
     */
    public function handleRequest(): Response
    {
        $session = SessionManager::getInstance();
        if ($session->get('logged_in') === true) {
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
        $session = SessionManager::getInstance();

        // Redirect already-logged-in users away from login form
        if ($session->get('logged_in') === true && !isset($_POST['logout'])) {
            return Response::redirect('/home');
        }

        // Handle logout
        if ($session->get('logged_in') === true && isset($_POST['logout'])) {
            if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                MessageHelper::addMessage('Invalid CSRF token. Please try again.');
                return Response::redirect('/login');
            }
            return self::logoutUser();
        }

        // Validate CSRF token
        if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token. Please try again.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
            return Response::view('login');
        }

        // Trim and validate input
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate credentials
        if (self::validateCredentials($username, $password)) {
            $session->set('logged_in', true);
            $session->set('username', $username);
            $session->set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $session->set('csrf_token', \hash('sha256', \uniqid('', true)));
            $session->set('timeout', time());
            $session->regenerate();
            return Response::redirect('/home');
        }

        // Handle failed login attempt
        $ip = $_SERVER['REMOTE_ADDR'];
        if (BlacklistModel::isBlacklisted($ip)) {
            $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
        } else {
            BlacklistModel::updateFailedAttempts($ip);
            $error = 'Invalid username or password.';
            ErrorManager::getInstance()->log($error);
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
    private static function validateCredentials(string $username, string $password): bool
    {
        $validatedUsername = ValidationHelper::validateUsername($username);
        $validatedPassword = ValidationHelper::validatePassword($password);

        return $validatedUsername === VALID_USERNAME
            && $validatedPassword !== null
            && password_verify($validatedPassword, VALID_PASSWORD_HASH);
    }

    /**
     * Destroy the session and redirect to login page.
     *
     * @return Response
     */
    private static function logoutUser(): Response
    {
        SessionManager::getInstance()->destroy();
        return Response::redirect('/login');
    }
}
