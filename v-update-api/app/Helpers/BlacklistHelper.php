<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: BlacklistHelper.php
 * Description: IP blacklist checking helper
 */

namespace App\Helpers;

use App\Core\DatabaseManager;
use App\Core\ErrorManager;
use App\Models\BlacklistModel;

/**
 * Static helper for IP blacklist operations.
 */
class BlacklistHelper
{
    /**
     * Determine whether the current request originates from a blacklisted IP.
     *
     * @return bool True when remote IP is valid and blacklisted.
     */
    public static function isBlacklisted(): bool
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if (!$ip) {
            return false;
        }

        $blacklistModel = new BlacklistModel((new DatabaseManager())->getConnection());
        if ($blacklistModel->isBlacklisted($ip)) {
            ErrorManager::log("Blacklisted IP attempted access: $ip", 'error');
            return true;
        }

        return false;
    }
}
