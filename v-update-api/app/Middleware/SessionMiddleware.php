<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\RequestManager;
use App\Core\ResponseManager;
use App\Core\SessionManager;
use App\Helpers\EncryptionHelper;

class SessionMiddleware
{
    public function __invoke(RequestManager $request, callable $next): ResponseManager
    {
        if (!SessionManager::getInstance()->get('csrf_token')) {
            SessionManager::getInstance()->set('csrf_token', bin2hex(EncryptionHelper::bytes(32)));
        }

        if ($request->path !== '/login' && !str_starts_with($request->path, '/api')) {
            if (!$this->isValid()) {
                return ResponseManager::redirect('/login');
            }
        }

        return $next($request);
    }

    private function isValid(): bool
    {
        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeout = SessionManager::getInstance()->get('timeout');
        $timeoutExceeded = is_int($timeout) && (time() - $timeout > $timeoutLimit);

        $userAgent = SessionManager::getInstance()->get('user_agent');
        $userAgentChanged = is_string($userAgent) && $userAgent !== ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($timeoutExceeded || $userAgentChanged) {
            SessionManager::getInstance()->destroy();
            return false;
        }

        SessionManager::getInstance()->set('timeout', time());

        return SessionManager::getInstance()->get('logged_in') === true;
    }
}
