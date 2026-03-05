<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: ValidationHelper.php
 * Description: Validation helper using Respect\Validation
 */

namespace App\Helpers;

use Respect\Validation\Validator as v;
use App\Core\SessionManager;

class ValidationHelper
{
    public static function validateDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        $rule = v::domain()->not(v::startsWith('-'));
        return $rule->validate($domain) ? $domain : null;
    }

    public static function validateKey(string $key): ?string
    {
        $key = trim($key);
        $rule = v::alnum('-_')->noWhitespace();
        return $rule->validate($key) ? $key : null;
    }

    public static function generateKey(int $length = 32): string
    {
        $bytes = \random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    public static function validateSlug(string $slug): ?string
    {
        $slug = basename(trim($slug));
        $rule = v::regex('/^[A-Za-z0-9._-]+$/');
        return $rule->validate($slug) ? $slug : null;
    }

    public static function validateFilename(string $filename): ?string
    {
        $filename = basename(trim($filename));
        $rule = v::regex('/^[A-Za-z0-9_-]+_[0-9.]+\.zip$/');
        return $rule->validate($filename) ? $filename : null;
    }

    public static function validateVersion(string $version): ?string
    {
        $version = trim($version);
        $rule = v::regex('/^\d+(?:\.\d+)*$/');
        return $rule->validate($version) ? $version : null;
    }

    public static function validateUsername(string $username): ?string
    {
        $username = trim($username);
        $rule = v::alnum('._-')->length(3, 30);
        return $rule->validate($username) ? $username : null;
    }

    public static function validatePassword(string $password): ?string
    {
        $password = trim($password);
        return strlen($password) >= 6 ? $password : null;
    }

    /**
     * Validate a CSRF token against the session token.
     *
     * @param string $token Token provided by the client.
     * @return bool True when the token matches the session token.
     */
    public static function validateCsrfToken(string $token): bool
    {
        $sessionToken = SessionManager::getInstance()->get('csrf_token');
        return is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }
}
