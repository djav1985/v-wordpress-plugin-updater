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

    /**
     * Sanitize a raw HTTP response body for use in user-visible messages.
     *
     * Strips HTML tags to eliminate injection vectors (including </script> sequences
     * that could break out of <script> contexts when the message is rendered via
     * json_encode). Also neutralizes any residual </script sequences as defense-in-depth,
     * and truncates to a safe maximum length.
     *
     * @param mixed  $response The raw response value (may be false, null, or string).
     * @param string $fallback Value to return when the sanitized result is empty.
     * @return string Sanitized string safe for inclusion in user-visible messages.
     */
    public static function sanitizeErrorMessage(mixed $response, string $fallback = ''): string
    {
        $raw = is_string($response) ? $response : '';
        // strip_tags removes HTML tags including </script> sequences
        $sanitized = strip_tags($raw);
        // Defense-in-depth: neutralize any residual </script sequences
        $sanitized = str_replace('</script', '<\/script', $sanitized);
        $sanitized = mb_substr($sanitized, 0, 500);
        return $sanitized !== '' ? $sanitized : $fallback;
    }
}
