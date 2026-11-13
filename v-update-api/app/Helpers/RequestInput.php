<?php

declare(strict_types=1);

namespace App\Helpers;

/** Safely read scalar string values from untrusted request parameter arrays. */
final class RequestInput
{
    /** Return a string parameter, or null when missing or not a string. */
    /** @param array<string, mixed> $input */
    public static function string(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;
        return is_string($value) ? $value : null;
    }
}
