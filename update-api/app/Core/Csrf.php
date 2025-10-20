<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: Csrf.php
 * Description: CSRF validation helper
 */

namespace App\Core;

class Csrf
{
    public static function validate(string $token): bool
    {
        $sessionToken = SessionManager::getInstance()->get('csrf_token');
        if (!is_string($sessionToken) || !is_string($token) || $sessionToken === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}
