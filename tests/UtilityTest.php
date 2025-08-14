<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Core\Utility;

if (!defined('DATABASE_FILE')) {
    define('DATABASE_FILE', __DIR__ . '/blacklist_test.sqlite');
}

require_once __DIR__ . '/../update-api/app/Core/Utility.php';

final class UtilityTest extends TestCase
{
    protected function setUp(): void
    {
        if (file_exists(DATABASE_FILE)) {
            unlink(DATABASE_FILE);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists(DATABASE_FILE)) {
            unlink(DATABASE_FILE);
        }
    }

    public function testIpGetsBlacklistedAfterThreeFailedAttempts(): void
    {
        $ip = '127.0.0.1';
        Utility::updateFailedAttempts($ip);
        Utility::updateFailedAttempts($ip);
        Utility::updateFailedAttempts($ip);
        $this->assertTrue(Utility::isBlacklisted($ip));
    }

    public function testBlacklistExpiresAfterThreeDays(): void
    {
        $ip = '8.8.8.8';
        for ($i = 0; $i < 3; $i++) {
            Utility::updateFailedAttempts($ip);
        }

        $pdo = new \PDO('sqlite:' . DATABASE_FILE);
        $pdo->prepare('UPDATE blacklist SET timestamp = :ts WHERE ip = :ip')
            ->execute([
                ':ts' => time() - (4 * 24 * 60 * 60),
                ':ip' => $ip,
            ]);

        $this->assertFalse(Utility::isBlacklisted($ip));

        $stmt = $pdo->prepare('SELECT blacklisted, login_attempts FROM blacklist WHERE ip = :ip');
        $stmt->execute([':ip' => $ip]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, (int) $row['blacklisted']);
        $this->assertEquals(0, (int) $row['login_attempts']);
    }
}
