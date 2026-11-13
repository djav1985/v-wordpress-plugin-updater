<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: UpdateApiValidationTest.php
 * Description: Update API-specific PHPUnit tests for validation.
 */

declare(strict_types=1);

use App\Helpers\ValidationHelper;
use PHPUnit\Framework\TestCase;

final class UpdateApiValidationTest extends TestCase
{
    public function testDomainValidationNormalizesCaseAndWhitespace(): void
    {
        self::assertSame('example.com', ValidationHelper::validateDomain('  EXAMPLE.COM  '));
        self::assertNull(ValidationHelper::validateDomain('not_a_domain'));
        self::assertNull(ValidationHelper::validateDomain('-invalid.example'));
    }

    public function testApiKeyValidationTrimsAndRejectsUnsupportedCharacters(): void
    {
        self::assertSame('abc-123_XYZ', ValidationHelper::validateKey(' abc-123_XYZ '));
        self::assertNull(ValidationHelper::validateKey('api key'));
        self::assertNull(ValidationHelper::validateKey('api/key'));
    }

    public function testGenerateKeyReturnsHexadecimalOfRequestedLength(): void
    {
        self::assertMatchesRegularExpression('/\A[a-f0-9]{32}\z/', ValidationHelper::generateKey());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{17}\z/', ValidationHelper::generateKey(17));
    }

    public function testSlugValidationAcceptsBasenamesAndRejectsInvalidCharacters(): void
    {
        self::assertSame('plugin-name_2.0', ValidationHelper::validateSlug('../plugin-name_2.0'));
        self::assertNull(ValidationHelper::validateSlug('plugin name'));
    }

    public function testFilenameValidationAcceptsPackageZipNames(): void
    {
        self::assertSame('my-plugin_1.2.3.zip', ValidationHelper::validateFilename('../my-plugin_1.2.3.zip'));
        self::assertNull(ValidationHelper::validateFilename('my-plugin-1.2.3.zip'));
        self::assertNull(ValidationHelper::validateFilename('my-plugin_1.2.3.tar.gz'));
    }

    public function testPackageFilenameParserReturnsSlugAndVersion(): void
    {
        self::assertSame(
            ['slug' => 'my-plugin', 'version' => '1.2.3'],
            ValidationHelper::parsePackageFilename('../my-plugin_1.2.3.zip')
        );
        self::assertNull(ValidationHelper::parsePackageFilename('my-plugin-1.2.3.zip'));
    }

    public function testVersionValidationTrimsAndRejectsMalformedVersions(): void
    {
        self::assertSame('1.2.3', ValidationHelper::validateVersion(' 1.2.3 '));
        self::assertNull(ValidationHelper::validateVersion('v1.2.3'));
        self::assertNull(ValidationHelper::validateVersion('1..2'));
    }

    public function testUsernameValidationTrimsAndEnforcesLengthAndCharacters(): void
    {
        self::assertSame('Api_User-1', ValidationHelper::validateUsername(' Api_User-1 '));
        self::assertNull(ValidationHelper::validateUsername('ab'));
        self::assertNull(ValidationHelper::validateUsername('bad name'));
    }

    public function testPasswordValidationPreservesSubmittedWhitespace(): void
    {
        self::assertSame(' secret ', ValidationHelper::validatePassword(' secret '));
        self::assertNull(ValidationHelper::validatePassword('12345'));
        self::assertSame('123456', ValidationHelper::validatePassword('123456'));
    }

    public function testErrorMessageSanitizationStripsTagsAndUsesFallback(): void
    {
        self::assertSame('Provider error', ValidationHelper::sanitizeErrorMessage('<strong>Provider error</strong>'));
        self::assertSame('fallback', ValidationHelper::sanitizeErrorMessage(null, 'fallback'));
    }

    public function testErrorMessageSanitizationTruncatesLongResponses(): void
    {
        self::assertSame(
            str_repeat('a', 500),
            ValidationHelper::sanitizeErrorMessage(str_repeat('a', 501))
        );
    }
}
