<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: SessionHelper.php
 * Description: Static session management helper
 */

namespace App\Helpers;

/**
 * Static helper for PHP session and authentication operations.
 */
class SessionHelper
{
    /**
     * Initialize PHP session with secure settings.
     *
     * @return void
     */
    public static function initialize(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_secure', $secureFlag ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');

        session_set_cookie_params([
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secureFlag,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key     Session key to retrieve.
     * @param mixed  $default Default value if key does not exist.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::initialize();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     *
     * @param string $key   Session key to set.
     * @param mixed  $value Value to store.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::initialize();
        $_SESSION[$key] = $value;
    }

    /**
     * Destroy the current session and its data.
     *
     * @return void
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
            $_SESSION = [];
            session_destroy();
        }
    }

    /**
     * Regenerate the session ID to prevent fixation.
     *
     * @return void
     */
    public static function regenerate(): void
    {
        self::initialize();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Validate the current session and update timeout.
     *
     * @return bool True if the session is valid and user is logged in.
     */
    public static function isValid(): bool
    {
        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeout = self::get('timeout');
        $timeoutExceeded = is_int($timeout) && (time() - $timeout > $timeoutLimit);

        $userAgent = self::get('user_agent');
        $userAgentChanged = is_string($userAgent) && $userAgent !== ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($timeoutExceeded || $userAgentChanged) {
            self::destroy();
            return false;
        }

        self::set('timeout', time());

        return self::get('logged_in') === true;
    }
}
