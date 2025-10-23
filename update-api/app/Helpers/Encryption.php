<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: Encryption.php
 * Description: WordPress Update API
 */

namespace App\Helpers;

class Encryption
{
    /**
     * Encrypt a string using AES-256-CBC.
     *
     * @param string $plain Plain text to encrypt.
     * @return string Base64-encoded cipher text.
     */
    public static function encrypt(string $plain): string
    {
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = \random_bytes($iv_length);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    /**
     * Decrypt a string encrypted with encrypt().
     *
     * @param string $cipher Base64-encoded cipher text.
     * @return string|null Decrypted plain text or null on failure.
     */
    public static function decrypt(string $cipher): ?string
    {
        $data = base64_decode($cipher, true);
        if ($data === false) {
            return null;
        }
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($data) <= $iv_length) {
            return null;
        }
        $iv = substr($data, 0, $iv_length);
        $cipher_text = substr($data, $iv_length);
        $plain = openssl_decrypt(
            $cipher_text,
            'aes-256-cbc',
            hash('sha256', ENCRYPTION_KEY, true),
            OPENSSL_RAW_DATA,
            $iv
        );
        return $plain === false ? null : $plain;
    }
}
