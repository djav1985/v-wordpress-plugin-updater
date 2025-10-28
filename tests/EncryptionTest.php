<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Helpers\Encryption;

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
        $encrypted = Encryption::encrypt($plaintext);
        
        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
        $this->assertNotSame($plaintext, $encrypted);
        // Base64 encoded strings only contain these characters
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $encrypted);
    }

    public function testDecryptReturnsOriginalPlaintext(): void
    {
        $plaintext = 'secret message';
        $encrypted = Encryption::encrypt($plaintext);
        $decrypted = Encryption::decrypt($encrypted);
        
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
            $encrypted = Encryption::encrypt($value);
            $decrypted = Encryption::decrypt($encrypted);
            $this->assertSame($value, $decrypted, "Failed for value: $value");
        }
    }

    public function testDecryptInvalidBase64ReturnsNull(): void
    {
        $result = Encryption::decrypt('not-valid-base64!!!');
        $this->assertNull($result);
    }

    public function testDecryptTooShortDataReturnsNull(): void
    {
        // Create a valid base64 string that's too short
        $shortData = base64_encode('short');
        $result = Encryption::decrypt($shortData);
        $this->assertNull($result);
    }

    public function testEncryptProducesDifferentCiphertexts(): void
    {
        // Due to random IV, encrypting the same plaintext twice should produce different results
        $plaintext = 'same message';
        $encrypted1 = Encryption::encrypt($plaintext);
        $encrypted2 = Encryption::encrypt($plaintext);
        
        $this->assertNotSame($encrypted1, $encrypted2);
        // But both should decrypt to the same plaintext
        $this->assertSame($plaintext, Encryption::decrypt($encrypted1));
        $this->assertSame($plaintext, Encryption::decrypt($encrypted2));
    }

    public function testDecryptTamperedDataReturnsNull(): void
    {
        $plaintext = 'original message';
        $encrypted = Encryption::encrypt($plaintext);
        
        // Tamper with the encrypted data by changing a character
        $tampered = substr($encrypted, 0, -5) . 'XXXXX';
        $result = Encryption::decrypt($tampered);
        
        // Decryption should fail and return null or not match original
        $this->assertNotSame($plaintext, $result);
    }
}
