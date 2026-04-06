<?php

declare(strict_types=1);

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use App\Helpers\EncryptionHelper;
use PHPUnit\Framework\TestCase;

final class SecureRandomHelperTest extends TestCase
{
    public function testBytesReturnsRequestedLength(): void
    {
        $bytes = EncryptionHelper::bytes(32);

        self::assertSame(32, strlen($bytes));
    }

    public function testBytesThrowsForNonPositiveLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EncryptionHelper::bytes(0);
    }
}
