<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Helpers\WorkerHelper;

class WorkerHelperTest extends TestCase
{
    private string $testJobName = 'test-job';

    protected function setUp(): void
    {
        // Clean up any existing locks from previous tests
        $this->cleanupLockFile();
    }

    protected function tearDown(): void
    {
        // Clean up lock files after tests
        $this->cleanupLockFile();
    }

    private function cleanupLockFile(): void
    {
        $lockFile = sys_get_temp_dir() . '/v-updater-' . $this->testJobName . '.lock';
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
    }

    public function testCanLaunchReturnsTrueWhenNoLockExists(): void
    {
        $this->assertTrue(WorkerHelper::canLaunch($this->testJobName));
    }

    public function testClaimLockReturnsResourceWhenSuccessful(): void
    {
        $lock = WorkerHelper::claimLock($this->testJobName);
        $this->assertIsResource($lock);
        WorkerHelper::releaseLock($lock);
    }

    public function testClaimLockReturnsNullWhenLockAlreadyHeld(): void
    {
        $lock1 = WorkerHelper::claimLock($this->testJobName);
        $this->assertIsResource($lock1);
        
        // Try to claim the same lock again
        $lock2 = WorkerHelper::claimLock($this->testJobName);
        $this->assertNull($lock2);
        
        WorkerHelper::releaseLock($lock1);
    }

    public function testCanLaunchReturnsFalseWhenLockIsHeld(): void
    {
        $lock = WorkerHelper::claimLock($this->testJobName);
        $this->assertIsResource($lock);
        
        $this->assertFalse(WorkerHelper::canLaunch($this->testJobName));
        
        WorkerHelper::releaseLock($lock);
    }

    public function testCanLaunchReturnsTrueAfterLockIsReleased(): void
    {
        $lock = WorkerHelper::claimLock($this->testJobName);
        $this->assertIsResource($lock);
        
        WorkerHelper::releaseLock($lock);
        
        $this->assertTrue(WorkerHelper::canLaunch($this->testJobName));
    }

    public function testReleaseLockWithInvalidResourceDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        WorkerHelper::releaseLock(null);
    }

    public function testLockFileContainsPid(): void
    {
        $lock = WorkerHelper::claimLock($this->testJobName);
        $this->assertIsResource($lock);
        
        $lockFile = sys_get_temp_dir() . '/v-updater-' . $this->testJobName . '.lock';
        $this->assertFileExists($lockFile);
        
        $content = file_get_contents($lockFile);
        $this->assertEquals((string) getmypid(), $content);
        
        WorkerHelper::releaseLock($lock);
    }
}
