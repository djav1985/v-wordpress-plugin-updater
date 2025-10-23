<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: WorkerHelper.php
 * Description: Worker job management and locking helper
 */

namespace App\Helpers;

class WorkerHelper
{
    /**
     * Check if a worker job can be launched (not already running)
     *
     * @param string $jobName The name of the job
     * @return bool True if the job can be launched, false if already running
     */
    public static function canLaunch(string $jobName): bool
    {
        $lockFile = self::getLockFilePath($jobName);
        $handle = @fopen($lockFile, 'r');
        
        if ($handle === false) {
            // Lock file doesn't exist, job can be launched
            return true;
        }
        
        // Try to acquire an exclusive lock (non-blocking)
        $canLock = flock($handle, LOCK_EX | LOCK_NB);
        
        if ($canLock) {
            // We got the lock, which means no job is running
            flock($handle, LOCK_UN);
            fclose($handle);
            return true;
        }
        
        // Could not acquire lock, job is already running
        fclose($handle);
        return false;
    }
    
    /**
     * Claim a lock for a job
     *
     * @param string $jobName The name of the job
     * @return resource|null Lock handle if successful, null if lock already held
     */
    public static function claimLock(string $jobName): mixed
    {
        $lockFile = self::getLockFilePath($jobName);
        $handle = fopen($lockFile, 'c+');
        
        if ($handle === false) {
            return null;
        }
        
        // Try to acquire an exclusive lock (non-blocking)
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }
        
        // Write PID to lock file
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) getmypid());
        fflush($handle);
        
        return $handle;
    }
    
    /**
     * Release a lock for a job
     *
     * @param resource|null $lockHandle The lock handle returned by claimLock
     * @return void
     */
    public static function releaseLock(mixed $lockHandle): void
    {
        if (is_resource($lockHandle)) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }
    
    /**
     * Get the lock file path for a job
     *
     * @param string $jobName The name of the job
     * @return string The lock file path
     */
    private static function getLockFilePath(string $jobName): string
    {
        return sys_get_temp_dir() . '/v-updater-' . $jobName . '.lock';
    }
}
