<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseManager;
use App\Models\BlacklistModel;

class BlacklistModelTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/test-blacklist-model.sqlite');
        }
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
        $ref = new \ReflectionClass(DatabaseManager::class);
        $prop = $ref->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement(
            'CREATE TABLE blacklist (ip TEXT PRIMARY KEY, login_attempts INTEGER, blacklisted INTEGER, timestamp INTEGER)'
        );
    }

    protected function tearDown(): void
    {
        $conn = DatabaseManager::getConnection();
        $conn->executeStatement('DROP TABLE IF EXISTS blacklist');
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
    }

    public function testUpdateFailedAttemptsIncreasesCount(): void
    {
        $ip = '192.168.1.1';
        
        BlacklistModel::updateFailedAttempts($ip);
        
        $conn = DatabaseManager::getConnection();
        $record = $conn->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);
        
        $this->assertSame(1, (int)$record['login_attempts']);
        $this->assertSame(0, (int)$record['blacklisted']);
    }

    public function testUpdateFailedAttemptsBlacklistsAfterThreeAttempts(): void
    {
        $ip = '192.168.1.2';
        
        BlacklistModel::updateFailedAttempts($ip);
        BlacklistModel::updateFailedAttempts($ip);
        BlacklistModel::updateFailedAttempts($ip);
        
        $conn = DatabaseManager::getConnection();
        $record = $conn->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);
        
        $this->assertSame(3, (int)$record['login_attempts']);
        $this->assertSame(1, (int)$record['blacklisted']);
    }

    public function testIsBlacklistedReturnsFalseForNewIp(): void
    {
        $ip = '192.168.1.3';
        $this->assertFalse(BlacklistModel::isBlacklisted($ip));
    }

    public function testIsBlacklistedReturnsTrueForBlacklistedIp(): void
    {
        $ip = '192.168.1.4';
        
        $conn = DatabaseManager::getConnection();
        $conn->insert('blacklist', [
            'ip' => $ip,
            'login_attempts' => 3,
            'blacklisted' => 1,
            'timestamp' => time(),
        ]);
        
        $this->assertTrue(BlacklistModel::isBlacklisted($ip));
    }

    public function testIsBlacklistedReturnsFalseForNonBlacklistedIp(): void
    {
        $ip = '192.168.1.5';
        
        $conn = DatabaseManager::getConnection();
        $conn->insert('blacklist', [
            'ip' => $ip,
            'login_attempts' => 2,
            'blacklisted' => 0,
            'timestamp' => time(),
        ]);
        
        $this->assertFalse(BlacklistModel::isBlacklisted($ip));
    }

    public function testIsBlacklistedResetsExpiredBlacklist(): void
    {
        $ip = '192.168.1.6';
        
        // Set a blacklist that expired more than 3 days ago
        $conn = DatabaseManager::getConnection();
        $conn->insert('blacklist', [
            'ip' => $ip,
            'login_attempts' => 3,
            'blacklisted' => 1,
            'timestamp' => time() - (4 * 24 * 60 * 60), // 4 days ago
        ]);
        
        // Should return false and reset the blacklist
        $this->assertFalse(BlacklistModel::isBlacklisted($ip));
        
        // Verify the record was updated
        $record = $conn->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);
        $this->assertSame(0, (int)$record['blacklisted']);
        $this->assertSame(0, (int)$record['login_attempts']);
    }

    public function testUpdateFailedAttemptsUpdatesTimestampOnBlacklist(): void
    {
        $ip = '192.168.1.7';
        $conn = DatabaseManager::getConnection();
        
        // Insert with old timestamp
        $oldTime = time() - 1000;
        $conn->insert('blacklist', [
            'ip' => $ip,
            'login_attempts' => 2,
            'blacklisted' => 0,
            'timestamp' => $oldTime,
        ]);
        
        // Third attempt should blacklist and update timestamp
        BlacklistModel::updateFailedAttempts($ip);
        
        $record = $conn->fetchAssociative('SELECT * FROM blacklist WHERE ip = ?', [$ip]);
        $this->assertSame(1, (int)$record['blacklisted']);
        $this->assertGreaterThan($oldTime, (int)$record['timestamp']);
    }
}
