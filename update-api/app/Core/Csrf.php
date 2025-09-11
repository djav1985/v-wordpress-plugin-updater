<?php

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
