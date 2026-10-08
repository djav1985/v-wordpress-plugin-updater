<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: SessionManager.php
 * Description: Singleton session management manager
 */

namespace App\Core;

/**
 * Singleton manager for PHP session and authentication operations.
 */
class SessionManager
{
    private static ?self $instance = null;

    /**
     * Prevent direct instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton session manager instance.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key     Session key to retrieve.
     * @param mixed  $default Default value if key does not exist.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->initialize();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     *
     * @param string $key   Session key to set.
     * @param mixed  $value Value to store.
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->initialize();
        $_SESSION[$key] = $value;
    }

    /**
     * Destroy the current session and its data.
     *
     * @return void
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
            session_unset();
            session_destroy();
        }
    }

    /**
     * Regenerate the session ID to prevent fixation.
     *
     * @return void
     */
    public function regenerate(): void
    {
        $this->initialize();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Initialize PHP session with secure settings.
     *
     * @return void
     */
    private function initialize(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_secure', $secureFlag ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');

        session_set_cookie_params([
            'path' => '/',
            'httponly' => true,
            'secure' => $secureFlag,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}