<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: index.php
 * Description: WordPress Update API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\ErrorManager;
use App\Core\RequestManager;
use App\Core\Router;

ErrorManager::handle(function (): void {
    // Build router
    $router = new Router();

    // Dispatch request through router
    $request = RequestManager::fromGlobals();
    $response = $router->dispatch($request);
    $response->send();
});

