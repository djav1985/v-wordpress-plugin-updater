<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Helpers\ValidationHelper;

class ValidationTest extends TestCase
{
    public function testValidateDomainAcceptsValidDomains(): void
    {
        $this->assertSame('example.com', ValidationHelper::validateDomain('example.com'));
        $this->assertSame('sub.example.com', ValidationHelper::validateDomain('sub.example.com'));
        $this->assertSame('example.co.uk', ValidationHelper::validateDomain('example.co.uk'));
        $this->assertSame('test-site.com', ValidationHelper::validateDomain('test-site.com'));
    }

    public function testValidateDomainRejectsInvalidDomains(): void
    {
        $this->assertNull(ValidationHelper::validateDomain(''));
        $this->assertNull(ValidationHelper::validateDomain('-example.com'));
        $this->assertNull(ValidationHelper::validateDomain('example .com'));
        $this->assertNull(ValidationHelper::validateDomain('not a domain'));
    }

    public function testValidateDomainTrimsAndLowercases(): void
    {
        $this->assertSame('example.com', ValidationHelper::validateDomain(' EXAMPLE.COM '));
    }

    public function testValidateKeyAcceptsValidKeys(): void
    {
        $this->assertSame('abc123', ValidationHelper::validateKey('abc123'));
        $this->assertSame('key-with-dashes', ValidationHelper::validateKey('key-with-dashes'));
        $this->assertSame('key_with_underscores', ValidationHelper::validateKey('key_with_underscores'));
        $this->assertSame('MixedCaseKey123', ValidationHelper::validateKey('MixedCaseKey123'));
    }

    public function testValidateKeyRejectsInvalidKeys(): void
    {
        $this->assertNull(ValidationHelper::validateKey(''));
        $this->assertNull(ValidationHelper::validateKey('key with spaces'));
        $this->assertNull(ValidationHelper::validateKey('key@special'));
        $this->assertNull(ValidationHelper::validateKey('key.dot'));
    }

    public function testGenerateKeyReturnsCorrectLength(): void
    {
        $key = ValidationHelper::generateKey(32);
        $this->assertSame(32, strlen($key));
        
        $key16 = ValidationHelper::generateKey(16);
        $this->assertSame(16, strlen($key16));
        
        $key64 = ValidationHelper::generateKey(64);
        $this->assertSame(64, strlen($key64));
    }

    public function testGenerateKeyReturnsHexString(): void
    {
        $key = ValidationHelper::generateKey(32);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
    }

    public function testGenerateKeyProducesDifferentKeys(): void
    {
        $key1 = ValidationHelper::generateKey();
        $key2 = ValidationHelper::generateKey();
        $this->assertNotSame($key1, $key2);
    }

    public function testValidateSlugAcceptsValidSlugs(): void
    {
        $this->assertSame('my-plugin', ValidationHelper::validateSlug('my-plugin'));
        $this->assertSame('plugin_name', ValidationHelper::validateSlug('plugin_name'));
        $this->assertSame('plugin.name', ValidationHelper::validateSlug('plugin.name'));
        $this->assertSame('PluginName123', ValidationHelper::validateSlug('PluginName123'));
    }

    public function testValidateSlugRejectsInvalidSlugs(): void
    {
        $this->assertNull(ValidationHelper::validateSlug(''));
        $this->assertNull(ValidationHelper::validateSlug('slug with spaces'));
        $this->assertNull(ValidationHelper::validateSlug('slug@special'));
    }

    public function testValidateSlugUsesBasename(): void
    {
        $this->assertSame('plugin-name', ValidationHelper::validateSlug('/path/to/plugin-name'));
    }

    public function testValidateFilenameAcceptsValidFilenames(): void
    {
        $this->assertSame('my-plugin_1.0.zip', ValidationHelper::validateFilename('my-plugin_1.0.zip'));
        $this->assertSame('plugin_2.1.3.zip', ValidationHelper::validateFilename('plugin_2.1.3.zip'));
        $this->assertSame('Test-Plugin_1.0.0.zip', ValidationHelper::validateFilename('Test-Plugin_1.0.0.zip'));
    }

    public function testValidateFilenameRejectsInvalidFilenames(): void
    {
        $this->assertNull(ValidationHelper::validateFilename(''));
        $this->assertNull(ValidationHelper::validateFilename('plugin.zip'));
        $this->assertNull(ValidationHelper::validateFilename('plugin_1.0.txt'));
        $this->assertNull(ValidationHelper::validateFilename('no-version.zip'));
        $this->assertNull(ValidationHelper::validateFilename('plugin with spaces_1.0.zip'));
    }

    public function testValidateVersionAcceptsValidVersions(): void
    {
        $this->assertSame('1.0', ValidationHelper::validateVersion('1.0'));
        $this->assertSame('2.1.3', ValidationHelper::validateVersion('2.1.3'));
        $this->assertSame('10.20.30.40', ValidationHelper::validateVersion('10.20.30.40'));
        $this->assertSame('1', ValidationHelper::validateVersion('1'));
    }

    public function testValidateVersionRejectsInvalidVersions(): void
    {
        $this->assertNull(ValidationHelper::validateVersion(''));
        $this->assertNull(ValidationHelper::validateVersion('v1.0'));
        $this->assertNull(ValidationHelper::validateVersion('1.0-beta'));
        $this->assertNull(ValidationHelper::validateVersion('a.b.c'));
        $this->assertNull(ValidationHelper::validateVersion('1..0'));
    }

    public function testValidateUsernameAcceptsValidUsernames(): void
    {
        $this->assertSame('admin', ValidationHelper::validateUsername('admin'));
        $this->assertSame('user123', ValidationHelper::validateUsername('user123'));
        $this->assertSame('user.name', ValidationHelper::validateUsername('user.name'));
        $this->assertSame('user_name', ValidationHelper::validateUsername('user_name'));
        $this->assertSame('user-name', ValidationHelper::validateUsername('user-name'));
    }

    public function testValidateUsernameRejectsInvalidUsernames(): void
    {
        $this->assertNull(ValidationHelper::validateUsername(''));
        $this->assertNull(ValidationHelper::validateUsername('ab')); // Too short
        $this->assertNull(ValidationHelper::validateUsername(str_repeat('a', 31))); // Too long
        $this->assertNull(ValidationHelper::validateUsername('user name'));
        $this->assertNull(ValidationHelper::validateUsername('user@name'));
    }

    public function testValidatePasswordAcceptsValidPasswords(): void
    {
        $this->assertSame('password', ValidationHelper::validatePassword('password'));
        $this->assertSame('123456', ValidationHelper::validatePassword('123456'));
        $this->assertSame('Pass@123', ValidationHelper::validatePassword('Pass@123'));
        $this->assertSame('long password with spaces', ValidationHelper::validatePassword('long password with spaces'));
    }

    public function testValidatePasswordRejectsShortPasswords(): void
    {
        $this->assertNull(ValidationHelper::validatePassword(''));
        $this->assertNull(ValidationHelper::validatePassword('12345'));
        $this->assertNull(ValidationHelper::validatePassword('pass'));
    }
}
