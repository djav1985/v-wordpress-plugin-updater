<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: AuthMiddleware.php
 * Description: WordPress Update API
 */

namespace App\Core;

use App\Core\Utility;
use App\Core\ErrorMiddleware;

class AuthMiddleware
{
    public static function check(): void
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if ($ip && Utility::isBlacklisted($ip)) {
            http_response_code(403);
            ErrorMiddleware::logMessage("Blacklisted IP attempted access: $ip", 'error');
            exit();
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit();
        }

        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeoutExceeded = isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $timeoutLimit);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userAgentChanged = isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $userAgent;
        if ($timeoutExceeded || $userAgentChanged) {
            session_unset();
            session_destroy();
            header('Location: /login');
            exit();
        }

        $_SESSION['timeout'] = time();
    }
}
