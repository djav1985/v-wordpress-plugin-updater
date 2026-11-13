<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: UpdateApiEncryptionTest.php
 * Description: Update API-specific encryption and migration tests.
 */

declare(strict_types=1);

use App\Helpers\EncryptionHelper;
use PHPUnit\Framework\TestCase;

final class UpdateApiEncryptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('ENCRYPTION_KEY')) {
            define('ENCRYPTION_KEY', 'phpunit-encryption-test-key');
        }
    }

    public function testEncryptedPayloadRoundTripsAndDoesNotNeedMigration(): void
    {
        $ciphertext = EncryptionHelper::encrypt('api-key');

        self::assertFalse(EncryptionHelper::needsMigration($ciphertext));
        self::assertSame('api-key', EncryptionHelper::decrypt($ciphertext));
    }

    public function testDecryptRejectsMalformedAndTamperedAeadPayloads(): void
    {
        self::assertNull(EncryptionHelper::decrypt('not-base64!'));
        self::assertNull(EncryptionHelper::decrypt(base64_encode("\x01")));

        $ciphertext = EncryptionHelper::encrypt('api-key');
        $payload = base64_decode($ciphertext, true);
        self::assertNotFalse($payload);

        $payload[strlen($payload) - 1] = chr(ord($payload[strlen($payload) - 1]) ^ 1);
        $tamperedCiphertext = base64_encode($payload);

        self::assertNull(EncryptionHelper::decrypt($tamperedCiphertext));
        self::assertFalse(EncryptionHelper::needsMigration($tamperedCiphertext));
    }

    public function testLegacyCiphertextDecryptsAndIsMarkedForMigration(): void
    {
        $plainText = 'legacy-api-key';
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $encrypted = openssl_encrypt($plainText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        self::assertNotFalse($encrypted);

        $legacyCiphertext = base64_encode($iv . $encrypted);

        self::assertTrue(EncryptionHelper::needsMigration($legacyCiphertext));
        self::assertSame($plainText, EncryptionHelper::decrypt($legacyCiphertext));
    }

    public function testMigrationCheckRejectsInvalidBase64AndRecognizesLegacyFormat(): void
    {
        self::assertFalse(EncryptionHelper::needsMigration('not-base64!'));
        self::assertTrue(EncryptionHelper::needsMigration(base64_encode('legacy-payload')));
    }
}
