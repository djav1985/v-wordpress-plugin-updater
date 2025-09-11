<?php

namespace App\Core;

use App\Models\Blacklist;
use App\Core\ErrorManager;

class SessionManager
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'path'     => '/',
                'httponly' => true,
                'secure'   => $secure,
                'samesite' => 'Lax',
            ]);
            session_start();
            if (!$this->get('csrf_token')) {
                $this->set('csrf_token', bin2hex(random_bytes(32)));
            }
        }
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    private function isValid(): bool
    {
        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeout = $this->get('timeout');
        $timeoutExceeded = $timeout !== null && (time() - $timeout > $timeoutLimit);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userAgentChanged = ($this->get('user_agent') !== null && $this->get('user_agent') !== $userAgent);
        if ($timeoutExceeded || $userAgentChanged) {
            $this->destroy();
            return false;
        }

        $this->set('timeout', time());
        return $this->get('logged_in') === true;
    }

    public function requireAuth(): bool
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if ($ip && Blacklist::isBlacklisted($ip)) {
            ErrorManager::getInstance()->log("Blacklisted IP attempted access: $ip", 'error');
            http_response_code(403);
            return false;
        }

        if (!$this->isValid()) {
            header('Location: /login');
            exit();
        }

        return true;
    }
}
