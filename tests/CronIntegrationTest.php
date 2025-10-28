<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class CronIntegrationTest extends TestCase
{
    private string $cronPath;
    private string $lockPath;

    protected function setUp(): void
    {
        $this->cronPath = __DIR__ . '/../update-api/cron.php';
        $this->assertTrue(file_exists($this->cronPath), 'cron.php file must exist');
        $this->lockPath = sys_get_temp_dir() . '/v-updater-' . 'v-updater-cron' . '.lock';
        if (file_exists($this->lockPath)) {
            unlink($this->lockPath);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->lockPath) && file_exists($this->lockPath)) {
            unlink($this->lockPath);
        }
    }

    public function testCronExecutesDirectly(): void
    {
        // Initialize the database if not already done
        $this->initializeDatabase();

        $command = 'php ' . escapeshellarg($this->cronPath) . ' 2>&1';
        exec($command, $outputLines, $exitCode);
        $output = implode("\n", $outputLines);

        $this->assertSame(0, $exitCode, 'Cron should exit successfully');
        $this->assertStringContainsString('Cron job completed successfully', $output);
    }

    public function testCronWorkerLaunchesSuccessfully(): void
    {
        // Initialize the database if not already done
        $this->initializeDatabase();

        $command = 'php ' . escapeshellarg($this->cronPath) . ' --worker 2>&1';
        exec($command, $outputLines, $exitCode);

        $this->assertSame(0, $exitCode, 'Worker mode should exit successfully');
        $this->assertSame('', trim(implode("\n", $outputLines)), 'Worker mode should be silent');
    }

    public function testCronWorkerPositionalArgumentLaunchesSuccessfully(): void
    {
        $this->initializeDatabase();

        $command = 'php ' . escapeshellarg($this->cronPath) . ' worker 2>&1';
        exec($command, $outputLines, $exitCode);

        $this->assertSame(0, $exitCode, 'Positional worker mode should exit successfully');
        $this->assertSame('', trim(implode("\n", $outputLines)), 'Positional worker mode should be silent');
    }

    public function testCronRejectsUnknownArguments(): void
    {
        $command = 'php ' . escapeshellarg($this->cronPath) . ' --unknown 2>&1';
        exec($command, $outputLines, $exitCode);
        $output = implode("\n", $outputLines);

        $this->assertSame(1, $exitCode, 'Unknown arguments should cause a failure exit');
        $this->assertStringContainsString('Usage:', $output);
    }

    public function testCronCannotBeCalledFromWeb(): void
    {
        // Test that the script detects non-CLI execution
        $code = file_get_contents($this->cronPath);
        $this->assertStringContainsString("php_sapi_name() !== 'cli'", $code);
        $this->assertStringContainsString('Forbidden', $code);
    }

    public function testErrorManagerPropagatesNonZeroExit(): void
    {
        $autoloadPath = realpath(__DIR__ . '/../update-api/vendor/autoload.php');
        $this->assertNotFalse($autoloadPath, 'Autoload path must resolve');

        $tempScript = tempnam(sys_get_temp_dir(), 'cron-error-');
        $this->assertNotFalse($tempScript, 'Failed to create temp script');

        $scriptPath = $tempScript . '.php';
        $renamed = rename($tempScript, $scriptPath);
        $this->assertTrue($renamed, 'Failed to finalize temp script path');

        $script = <<<PHP
<?php
require '{$autoloadPath}';
use App\Core\ErrorManager;

ErrorManager::handle(function (): void {
    throw new \RuntimeException('boom');
});
PHP;

        file_put_contents($scriptPath, $script);

        $command = 'php ' . escapeshellarg($scriptPath) . ' 2>&1';
        exec($command, $outputLines, $exitCode);

        unlink($scriptPath);

        $this->assertSame(1, $exitCode, 'Exceptions should propagate as non-zero exit status');
        $this->assertNotEmpty($outputLines, 'CLI error output should be present');
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
