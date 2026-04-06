<?php
// phpcs:ignoreFile PSR1.Files.SideEffects

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: cron.php
 * Description: WordPress Update API
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\ErrorManager;
use App\Models\PluginModel;
use App\Models\ThemeModel;
use App\Models\BlacklistModel;

ErrorManager::handle(function (): void {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';
    require __DIR__ . '/config.php';

    $pluginModel = new PluginModel();
    $themeModel = new ThemeModel();
    $blacklistModel = new BlacklistModel();

    $pluginModel->syncFromDirectory(PLUGINS_DIR);
    $themeModel->syncFromDirectory(THEMES_DIR);
    $blacklistModel->cleanup();

    echo "Cron job completed successfully.\n";
});
