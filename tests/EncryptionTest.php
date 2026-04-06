<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Helpers\EncryptionHelper;

class EncryptionTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('ENCRYPTION_KEY')) {
            define('ENCRYPTION_KEY', 'test-encryption-key-for-testing-only');
        }
    }

    public function testEncryptReturnsBase64String(): void
    {
        $plaintext = 'secret message';
        $encrypted = EncryptionHelper::encrypt($plaintext);

        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
        $this->assertNotSame($plaintext, $encrypted);
        // Base64 encoded strings only contain these characters
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $encrypted);
    }

    public function testDecryptReturnsOriginalPlaintext(): void
    {
        $plaintext = 'secret message';
        $encrypted = EncryptionHelper::encrypt($plaintext);
        $decrypted = EncryptionHelper::decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptDecryptMultipleValues(): void
    {
        $values = [
            'short',
            'a longer message with spaces and punctuation!',
            'Special chars: @#$%^&*()',
            str_repeat('x', 1000), // Long string
        ];

        foreach ($values as $value) {
            $encrypted = EncryptionHelper::encrypt($value);
            $decrypted = EncryptionHelper::decrypt($encrypted);
            $this->assertSame($value, $decrypted, "Failed for value: $value");
        }
    }

    public function testDecryptInvalidBase64ReturnsNull(): void
    {
        $result = EncryptionHelper::decrypt('not-valid-base64!!!');
        $this->assertNull($result);
    }

    public function testDecryptTooShortDataReturnsNull(): void
    {
        // Create a valid base64 string that's too short (version byte only, no nonce/ciphertext/tag)
        $shortData = base64_encode("\x01short");
        $result = EncryptionHelper::decrypt($shortData);
        $this->assertNull($result);
    }

    public function testEncryptProducesDifferentCiphertexts(): void
    {
        // Due to random nonce, encrypting the same plaintext twice should produce different results
        $plaintext = 'same message';
        $encrypted1 = EncryptionHelper::encrypt($plaintext);
        $encrypted2 = EncryptionHelper::encrypt($plaintext);

        $this->assertNotSame($encrypted1, $encrypted2);
        // But both should decrypt to the same plaintext
        $this->assertSame($plaintext, EncryptionHelper::decrypt($encrypted1));
        $this->assertSame($plaintext, EncryptionHelper::decrypt($encrypted2));
    }

    public function testDecryptTamperedDataReturnsNull(): void
    {
        $plaintext = 'original message';
        $encrypted = EncryptionHelper::encrypt($plaintext);

        // Tamper with the encrypted data: flip last 5 chars (corrupts auth tag)
        $decoded   = base64_decode($encrypted, true);
        $tampered  = base64_encode(substr($decoded, 0, -5) . str_repeat("\xff", 5));
        $result    = EncryptionHelper::decrypt($tampered);

        // GCM authentication should reject tampered ciphertext
        $this->assertNull($result);
    }

    // ------------------------------------------------------------------
    // AEAD-specific tests
    // ------------------------------------------------------------------

    public function testNewCiphertextsAreNotMarkedAsLegacy(): void
    {
        $encrypted = EncryptionHelper::encrypt('test');
        $this->assertFalse(EncryptionHelper::needsMigration($encrypted));
    }

    public function testLegacyCbcCiphertextDecryptsCorrectly(): void
    {
        // Produce a legacy CBC ciphertext manually (old format: iv || ciphertext).
        $key      = hash('sha256', ENCRYPTION_KEY, true);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv       = random_bytes($ivLength);
        $cipher   = openssl_encrypt('legacy secret', 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $legacyCiphertext = base64_encode($iv . $cipher);

        $result = EncryptionHelper::decrypt($legacyCiphertext);
        $this->assertSame('legacy secret', $result);
    }

    public function testLegacyCbcCiphertextIsMarkedForMigration(): void
    {
        $key      = hash('sha256', ENCRYPTION_KEY, true);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv       = random_bytes($ivLength);
        $cipher   = openssl_encrypt('data', 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $legacyCiphertext = base64_encode($iv . $cipher);

        $this->assertTrue(EncryptionHelper::needsMigration($legacyCiphertext));
    }

    public function testNeedsMigrationReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse(EncryptionHelper::needsMigration('not-valid-base64!!!'));
        $this->assertFalse(EncryptionHelper::needsMigration(''));
    }
}
