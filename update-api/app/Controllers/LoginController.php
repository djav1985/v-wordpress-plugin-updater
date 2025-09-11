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
use App\Helpers\Validation;
use App\Models\Blacklist;
use App\Core\ErrorManager;
use App\Helpers\MessageHelper;
use App\Core\SessionManager;
use App\Core\Csrf;
use App\Core\Response;

class LoginController extends Controller
{
    public function handleRequest(): Response
    {
        $session = SessionManager::getInstance();
        if ($session->get('logged_in') === true) {
            return Response::redirect('/home');
        }
        return Response::view('login');
    }

    public function handleSubmission(): Response
    {
        $session = SessionManager::getInstance();
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            $error = 'Invalid CSRF token.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
            return Response::redirect('/login');
        }

        if (isset($_POST['logout'])) {
            return self::logoutUser();
        }

        $username = isset($_POST['username']) ? Validation::validateUsername($_POST['username']) : null;
        $password = isset($_POST['password']) ? Validation::validatePassword($_POST['password']) : null;
        if ($username === VALID_USERNAME && $password !== null && password_verify($password, VALID_PASSWORD_HASH)) {
            $session->set('logged_in', true);
            $session->set('username', $username);
            $session->set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $session->set('csrf_token', bin2hex(random_bytes(32)));
            $session->regenerate();
            return Response::redirect('/home');
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if (Blacklist::isBlacklisted($ip)) {
            $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
        } else {
            Blacklist::updateFailedAttempts($ip);
            $error = 'Invalid username or password.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
        }
        return Response::redirect('/login');
    }

    private static function logoutUser(): Response
    {
        SessionManager::getInstance()->destroy();
        return Response::redirect('/login');
    }
}
