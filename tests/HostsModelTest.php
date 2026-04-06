<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use App\Core\DatabaseManager;
use App\Helpers\EncryptionHelper;
use App\Models\HostsModel;
use PHPUnit\Framework\TestCase;

class HostsModelTest extends TestCase
{
    /** @var \Doctrine\DBAL\Connection */
    private $conn;

    protected function setUp(): void
    {
        if (!defined('DB_FILE')) {
            define('DB_FILE', sys_get_temp_dir() . '/test-hosts-model.sqlite');
        }
        if (!defined('ENCRYPTION_KEY')) {
            define('ENCRYPTION_KEY', 'hosts-model-test-key');
        }

        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }

        $ref = new \ReflectionClass(DatabaseManager::class);
        $prop = $ref->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->conn = DatabaseManager::getConnection();
        $this->conn->executeStatement('CREATE TABLE hosts (domain TEXT PRIMARY KEY, key TEXT NOT NULL)');
        $this->conn->executeStatement('CREATE TABLE logs (domain TEXT, type TEXT, date TEXT, status TEXT)');
    }

    protected function tearDown(): void
    {
        $this->conn->executeStatement('DROP TABLE IF EXISTS logs');
        $this->conn->executeStatement('DROP TABLE IF EXISTS hosts');

        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }
    }

    public function testUpdateEntryUpdatesExistingHostWithoutReinsertingRow(): void
    {
        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', EncryptionHelper::encrypt('old-key')]
        );

        $this->assertTrue(HostsModel::updateEntry('example.com', 'new-key'));

        $rowCount = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM hosts WHERE domain = ?', ['example.com']);
        $this->assertSame(1, $rowCount);

        $stored = (string) $this->conn->fetchOne('SELECT key FROM hosts WHERE domain = ?', ['example.com']);
        $this->assertNotSame('new-key', $stored);
        $this->assertSame('new-key', EncryptionHelper::decrypt($stored));

        $entries = HostsModel::getEntries();
        $this->assertSame('example.com', $entries[0]['domain']);
        $this->assertArrayHasKey('key', $entries[0]);
    }

    public function testUpdateEntryReturnsFalseWhenDomainDoesNotExist(): void
    {
        $this->assertFalse(HostsModel::updateEntry('missing.example', 'new-key'));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM hosts'));
    }

    public function testUpdateEntryPreservesReferentialIntegrityForDependentRows(): void
    {
        $this->conn->executeStatement('DROP TABLE logs');
        $this->conn->executeStatement(
            'CREATE TABLE logs (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT,' .
            'domain TEXT NOT NULL,' .
            'type TEXT,' .
            'date TEXT,' .
            'status TEXT,' .
            'FOREIGN KEY(domain) REFERENCES hosts(domain) ON DELETE RESTRICT' .
            ')'
        );

        $this->conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?)',
            ['example.com', EncryptionHelper::encrypt('old-key')]
        );
        $this->conn->executeStatement(
            'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
            ['example.com', 'plugin', '2026-01-01', 'ok']
        );

        $this->assertTrue(HostsModel::updateEntry('example.com', 'updated-key'));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM logs WHERE domain = ?', ['example.com']));

        $stored = (string) $this->conn->fetchOne('SELECT key FROM hosts WHERE domain = ?', ['example.com']);
        $this->assertSame('updated-key', EncryptionHelper::decrypt($stored));
    }
}
