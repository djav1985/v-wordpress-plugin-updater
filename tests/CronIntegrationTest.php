<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class CronIntegrationTest extends TestCase
{
    private string $cronPath;

    protected function setUp(): void
    {
        $this->cronPath = __DIR__ . '/../update-api/cron.php';
        $this->assertTrue(file_exists($this->cronPath), 'cron.php file must exist');
    }

    public function testCronUsageWithNoArguments(): void
    {
        $output = shell_exec("php {$this->cronPath} 2>&1");
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('php cron.php sync-reports', $output);
        $this->assertStringContainsString('php cron.php worker sync-reports', $output);
    }

    public function testCronUsageWithInvalidJob(): void
    {
        $output = shell_exec("php {$this->cronPath} invalid-job 2>&1");
        $this->assertStringContainsString('Usage:', $output);
    }

    public function testCronWorkerUsageWithInvalidJob(): void
    {
        $output = shell_exec("php {$this->cronPath} worker invalid-job 2>&1");
        $this->assertStringContainsString('Usage:', $output);
    }

    public function testCronWorkerUsageWithNoJob(): void
    {
        $output = shell_exec("php {$this->cronPath} worker 2>&1");
        $this->assertStringContainsString('Usage:', $output);
    }

    public function testCronExecutesWithCorrectJobName(): void
    {
        // Initialize the database if not already done
        $this->initializeDatabase();
        
        $output = shell_exec("php {$this->cronPath} sync-reports 2>&1");
        $this->assertStringContainsString('Cron job completed successfully', $output);
    }

    public function testCronWorkerLaunchesSuccessfully(): void
    {
        // Initialize the database if not already done
        $this->initializeDatabase();
        
        $output = shell_exec("php {$this->cronPath} worker sync-reports 2>&1");
        // Worker should launch silently and exit immediately
        $this->assertEmpty(trim($output));
    }

    public function testCronCannotBeCalledFromWeb(): void
    {
        // Test that the script detects non-CLI execution
        $code = file_get_contents($this->cronPath);
        $this->assertStringContainsString("php_sapi_name() !== 'cli'", $code);
        $this->assertStringContainsString('Forbidden', $code);
    }

    private function initializeDatabase(): void
    {
        $dbPath = __DIR__ . '/../update-api/storage/updater.sqlite';
        if (!file_exists($dbPath)) {
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            touch($dbPath);
        }
        
        // Initialize tables
        $pdo = new \PDO('sqlite:' . $dbPath);
        $pdo->exec('CREATE TABLE IF NOT EXISTS plugins (slug TEXT PRIMARY KEY, version TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS themes (slug TEXT PRIMARY KEY, version TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS blacklist (ip TEXT PRIMARY KEY, login_attempts INTEGER, blacklisted INTEGER, timestamp INTEGER)');
    }
}
