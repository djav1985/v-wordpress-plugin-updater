<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: Validation.php
 * Description: Validation helper using Respect\Validation
 */

namespace App\Helpers;

use Respect\Validation\Validator as v;

class Validation
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
        $bytes = random_bytes((int) ceil($length / 2));
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
}
<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: Validation.php
 * Description: WordPress Update API
 */

namespace App\Helpers;

class Validation
{
    /**
     * Validate a domain string.
     *
     * @param string $domain The domain to validate.
     * @return string|null The validated domain or null if invalid.
     */
    public static function validateDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));

        // Ensure the domain contains at least one dot and valid characters
        if (preg_match('/^(?!-)[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/', $domain)) {
            return $domain;
        }

        return null;
    }

    /**
     * Validate an API key or generic token.
     *
     * @param string $key The key to validate.
     * @return string|null The validated key or null if invalid.
     */
    public static function validateKey(string $key): ?string
    {
        $key = trim($key);
        return preg_match('/^[A-Za-z0-9_-]+$/', $key) ? $key : null;
    }

    /**
     * Generate a random API key.
     *
     * @param int $length Desired length of the key.
     * @return string Generated key consisting of hex characters.
     */
    public static function generateKey(int $length = 32): string
    {
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * Validate plugin or theme names and slugs.
     *
     * @param string $slug The slug to validate.
     * @return string|null The validated slug or null if invalid.
     */
    public static function validateSlug(string $slug): ?string
    {
        $slug = basename(trim($slug));
        return preg_match('/^[A-Za-z0-9._-]+$/', $slug) ? $slug : null;
    }

    /**
     * Validate uploaded file names.
     *
     * @param string $filename The filename to validate.
     * @return string|null The validated filename or null if invalid.
     */
    public static function validateFilename(string $filename): ?string
    {
        $filename = basename(trim($filename));
        return preg_match('/^[A-Za-z0-9_-]+_[0-9.]+\.zip$/', $filename) ? $filename : null;
    }

    /**
     * Validate a version number such as 1.0.0.
     *
     * @param string $version The version to validate.
     * @return string|null The validated version or null if invalid.
     */
    public static function validateVersion(string $version): ?string
    {
        $version = trim($version);
        return preg_match('/^\d+(?:\.\d+)*$/', $version) ? $version : null;
    }

    /**
     * Validate usernames for the admin interface.
     *
     * @param string $username The username to validate.
     * @return string|null The validated username or null if invalid.
     */
    public static function validateUsername(string $username): ?string
    {
        $username = trim($username);
        return preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username) ? $username : null;
    }

    /**
     * Basic password validation.
     *
     * @param string $password The password to validate.
     * @return string|null The validated password or null if invalid.
     */
    public static function validatePassword(string $password): ?string
    {
        $password = trim($password);
        return strlen($password) >= 6 ? $password : null;
    }

}
