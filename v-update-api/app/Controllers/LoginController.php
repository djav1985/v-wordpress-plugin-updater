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
use App\Core\Response;
use App\Helpers\RequestInput;
use Psr\Http\Message\ServerRequestInterface;

class LoginController
{
    /**
     * Display the login form when the user is not already authenticated.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        if (SessionManager::getInstance()->get('logged_in') === true) {
            return Response::redirect('/home');
        }
        return Response::view('login');
    }

    /**
     * Handle login form submission and logout actions.
     *
    * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $post = $request->getParsedBody();
        if (!is_array($post)) {
            return new Response(400);
        }

        // Redirect already-logged-in users away from login form
        if (SessionManager::getInstance()->get('logged_in') === true && !isset($post['logout'])) {
            return Response::redirect('/home');
        }

        // Handle logout
        if (SessionManager::getInstance()->get('logged_in') === true && isset($post['logout'])) {
            return $this->logoutUser();
        }

        // Trim and validate input
        $username = RequestInput::string($post, 'username') ?? '';
        $password = RequestInput::string($post, 'password') ?? '';

        // Validate credentials
        if ($this->validateCredentials($username, $password)) {
            SessionManager::getInstance()->set('logged_in', true);
            SessionManager::getInstance()->set('username', $username);
            SessionManager::getInstance()->set('user_agent', RequestInput::string($request->getServerParams(), 'HTTP_USER_AGENT') ?? '');
            SessionManager::getInstance()->set('csrf_token', \bin2hex(EncryptionHelper::bytes(32)));
            SessionManager::getInstance()->set('timeout', time());
            SessionManager::getInstance()->regenerate();
            return Response::redirect('/home');
        }

        // Handle failed login attempt
        $ip = filter_var(RequestInput::string($request->getServerParams(), 'REMOTE_ADDR') ?? '', FILTER_VALIDATE_IP);
        if (!$ip) {
            $error = 'Unable to determine client IP.';
            ErrorManager::log($error);
            MessageHelper::addMessage($error);
            return Response::view('login');
        }

        BlacklistModel::updateFailedAttempts($ip);
        $error = 'Invalid username or password.';
        ErrorManager::log($error);
        MessageHelper::addMessage($error);

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
        SessionManager::getInstance()->destroy();
        return Response::redirect('/login');
    }
}
