<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: EncryptionHelper.php
 * Description: WordPress Update API
 */

namespace App\Helpers;

class EncryptionHelper
{
    /**
     * Version byte that marks an AEAD (AES-256-GCM) payload.
     * Absent from legacy CBC ciphertexts, so its presence is unambiguous.
     */
    private const VERSION_AEAD = "\x01";

    /** GCM nonce length in bytes. */
    private const NONCE_LENGTH = 12;

    /** GCM authentication-tag length in bytes. */
    private const TAG_LENGTH = 16;

    /**
     * Encrypt a string using AES-256-GCM (authenticated encryption).
     *
     * Payload format (before base64):
     *   version(1) || nonce(12) || ciphertext || auth_tag(16)
     *
     * @param string $plain Plain text to encrypt.
     * @return string Base64-encoded AEAD payload.
     * @throws \RuntimeException When secure random bytes cannot be generated.
     */
    public static function encrypt(string $plain): string
    {
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $nonce = self::bytes(self::NONCE_LENGTH);
        $tag = '';

        $cipherText = openssl_encrypt(
            $plain,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($cipherText === false) {
            throw new \RuntimeException('AES-256-GCM encryption failed.');
        }

        return base64_encode(self::VERSION_AEAD . $nonce . $cipherText . $tag);
    }

    /**
     * Decrypt a ciphertext produced by encrypt() (AES-256-GCM) or by the
     * legacy AES-256-CBC implementation (automatic fallback).
     *
     * @param string $cipher Base64-encoded ciphertext.
     * @return string|null Decrypted plain text, or null on failure.
     */
    public static function decrypt(string $cipher): ?string
    {
        $data = base64_decode($cipher, true);
        if ($data === false || $data === '') {
            return null;
        }

        if (substr($data, 0, 1) === self::VERSION_AEAD) {
            return self::decryptAead($data);
        }

        return self::decryptLegacy($data);
    }

    /**
     * Returns true when the ciphertext was produced by the legacy CBC scheme
     * and should be re-encrypted after a successful decrypt.
     *
     * @param string $cipher Base64-encoded ciphertext.
     */
    public static function needsMigration(string $cipher): bool
    {
        $data = base64_decode($cipher, true);
        if ($data === false || $data === '') {
            return false;
        }
        return substr($data, 0, 1) !== self::VERSION_AEAD;
    }

    /**
     * Return cryptographically secure random bytes.
     *
     * @param int $length Number of bytes to generate.
     * @return string
     * @throws \InvalidArgumentException When $length is not positive.
     * @throws \RuntimeException When secure random bytes cannot be generated.
     */
    public static function bytes(int $length): string
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length must be greater than zero.');
        }

        $isStrong = false;
        $bytes = openssl_random_pseudo_bytes($length, $isStrong);

        if ($bytes === false || $isStrong !== true || strlen($bytes) !== $length) {
            throw new \RuntimeException('Unable to generate cryptographically secure random bytes.');
        }

        return $bytes;
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Decrypt an AEAD (AES-256-GCM) payload.
     *
     * @param string $data Raw (decoded) payload bytes.
     * @return string|null
     */
    private static function decryptAead(string $data): ?string
    {
        // version(1) + nonce(12) + at least 1 byte ciphertext + tag(16)
        $minLen = 1 + self::NONCE_LENGTH + 1 + self::TAG_LENGTH;
        if (strlen($data) < $minLen) {
            return null;
        }

        $nonce      = substr($data, 1, self::NONCE_LENGTH);
        $tag        = substr($data, -(self::TAG_LENGTH));
        $cipherText = substr($data, 1 + self::NONCE_LENGTH, strlen($data) - 1 - self::NONCE_LENGTH - self::TAG_LENGTH);

        $key   = hash('sha256', ENCRYPTION_KEY, true);
        $plain = openssl_decrypt(
            $cipherText,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        return $plain === false ? null : $plain;
    }

    /**
     * Decrypt a legacy AES-256-CBC payload (iv || ciphertext).
     *
     * @param string $data Raw (decoded) payload bytes.
     * @return string|null
     */
    private static function decryptLegacy(string $data): ?string
    {
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        if ($ivLength === false || strlen($data) <= $ivLength) {
            return null;
        }

        $iv         = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);
        $key        = hash('sha256', ENCRYPTION_KEY, true);

        $plain = openssl_decrypt($cipherText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }
}
