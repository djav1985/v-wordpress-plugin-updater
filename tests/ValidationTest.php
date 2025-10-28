<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Helpers\Validation;

class ValidationTest extends TestCase
{
    public function testValidateDomainAcceptsValidDomains(): void
    {
        $this->assertSame('example.com', Validation::validateDomain('example.com'));
        $this->assertSame('sub.example.com', Validation::validateDomain('sub.example.com'));
        $this->assertSame('example.co.uk', Validation::validateDomain('example.co.uk'));
        $this->assertSame('test-site.com', Validation::validateDomain('test-site.com'));
    }

    public function testValidateDomainRejectsInvalidDomains(): void
    {
        $this->assertNull(Validation::validateDomain(''));
        $this->assertNull(Validation::validateDomain('-example.com'));
        $this->assertNull(Validation::validateDomain('example .com'));
        $this->assertNull(Validation::validateDomain('not a domain'));
    }

    public function testValidateDomainTrimsAndLowercases(): void
    {
        $this->assertSame('example.com', Validation::validateDomain(' EXAMPLE.COM '));
    }

    public function testValidateKeyAcceptsValidKeys(): void
    {
        $this->assertSame('abc123', Validation::validateKey('abc123'));
        $this->assertSame('key-with-dashes', Validation::validateKey('key-with-dashes'));
        $this->assertSame('key_with_underscores', Validation::validateKey('key_with_underscores'));
        $this->assertSame('MixedCaseKey123', Validation::validateKey('MixedCaseKey123'));
    }

    public function testValidateKeyRejectsInvalidKeys(): void
    {
        $this->assertNull(Validation::validateKey(''));
        $this->assertNull(Validation::validateKey('key with spaces'));
        $this->assertNull(Validation::validateKey('key@special'));
        $this->assertNull(Validation::validateKey('key.dot'));
    }

    public function testGenerateKeyReturnsCorrectLength(): void
    {
        $key = Validation::generateKey(32);
        $this->assertSame(32, strlen($key));
        
        $key16 = Validation::generateKey(16);
        $this->assertSame(16, strlen($key16));
        
        $key64 = Validation::generateKey(64);
        $this->assertSame(64, strlen($key64));
    }

    public function testGenerateKeyReturnsHexString(): void
    {
        $key = Validation::generateKey(32);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
    }

    public function testGenerateKeyProducesDifferentKeys(): void
    {
        $key1 = Validation::generateKey();
        $key2 = Validation::generateKey();
        $this->assertNotSame($key1, $key2);
    }

    public function testValidateSlugAcceptsValidSlugs(): void
    {
        $this->assertSame('my-plugin', Validation::validateSlug('my-plugin'));
        $this->assertSame('plugin_name', Validation::validateSlug('plugin_name'));
        $this->assertSame('plugin.name', Validation::validateSlug('plugin.name'));
        $this->assertSame('PluginName123', Validation::validateSlug('PluginName123'));
    }

    public function testValidateSlugRejectsInvalidSlugs(): void
    {
        $this->assertNull(Validation::validateSlug(''));
        $this->assertNull(Validation::validateSlug('slug with spaces'));
        $this->assertNull(Validation::validateSlug('slug@special'));
    }

    public function testValidateSlugUsesBasename(): void
    {
        $this->assertSame('plugin-name', Validation::validateSlug('/path/to/plugin-name'));
    }

    public function testValidateFilenameAcceptsValidFilenames(): void
    {
        $this->assertSame('my-plugin_1.0.zip', Validation::validateFilename('my-plugin_1.0.zip'));
        $this->assertSame('plugin_2.1.3.zip', Validation::validateFilename('plugin_2.1.3.zip'));
        $this->assertSame('Test-Plugin_1.0.0.zip', Validation::validateFilename('Test-Plugin_1.0.0.zip'));
    }

    public function testValidateFilenameRejectsInvalidFilenames(): void
    {
        $this->assertNull(Validation::validateFilename(''));
        $this->assertNull(Validation::validateFilename('plugin.zip'));
        $this->assertNull(Validation::validateFilename('plugin_1.0.txt'));
        $this->assertNull(Validation::validateFilename('no-version.zip'));
        $this->assertNull(Validation::validateFilename('plugin with spaces_1.0.zip'));
    }

    public function testValidateVersionAcceptsValidVersions(): void
    {
        $this->assertSame('1.0', Validation::validateVersion('1.0'));
        $this->assertSame('2.1.3', Validation::validateVersion('2.1.3'));
        $this->assertSame('10.20.30.40', Validation::validateVersion('10.20.30.40'));
        $this->assertSame('1', Validation::validateVersion('1'));
    }

    public function testValidateVersionRejectsInvalidVersions(): void
    {
        $this->assertNull(Validation::validateVersion(''));
        $this->assertNull(Validation::validateVersion('v1.0'));
        $this->assertNull(Validation::validateVersion('1.0-beta'));
        $this->assertNull(Validation::validateVersion('a.b.c'));
        $this->assertNull(Validation::validateVersion('1..0'));
    }

    public function testValidateUsernameAcceptsValidUsernames(): void
    {
        $this->assertSame('admin', Validation::validateUsername('admin'));
        $this->assertSame('user123', Validation::validateUsername('user123'));
        $this->assertSame('user.name', Validation::validateUsername('user.name'));
        $this->assertSame('user_name', Validation::validateUsername('user_name'));
        $this->assertSame('user-name', Validation::validateUsername('user-name'));
    }

    public function testValidateUsernameRejectsInvalidUsernames(): void
    {
        $this->assertNull(Validation::validateUsername(''));
        $this->assertNull(Validation::validateUsername('ab')); // Too short
        $this->assertNull(Validation::validateUsername(str_repeat('a', 31))); // Too long
        $this->assertNull(Validation::validateUsername('user name'));
        $this->assertNull(Validation::validateUsername('user@name'));
    }

    public function testValidatePasswordAcceptsValidPasswords(): void
    {
        $this->assertSame('password', Validation::validatePassword('password'));
        $this->assertSame('123456', Validation::validatePassword('123456'));
        $this->assertSame('Pass@123', Validation::validatePassword('Pass@123'));
        $this->assertSame('long password with spaces', Validation::validatePassword('long password with spaces'));
    }

    public function testValidatePasswordRejectsShortPasswords(): void
    {
        $this->assertNull(Validation::validatePassword(''));
        $this->assertNull(Validation::validatePassword('12345'));
        $this->assertNull(Validation::validatePassword('pass'));
    }
}
