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
use App\Core\Request;
use App\Core\Router;
use App\Helpers\EncryptionHelper;
use App\Helpers\SessionHelper;

ErrorManager::handle(function (): void {
    // Initialize CSRF token if not set
    if (!SessionHelper::get('csrf_token')) {
        SessionHelper::set('csrf_token', bin2hex(EncryptionHelper::bytes(32)));
    }

    // Build router
    $router = new Router();

    // Dispatch request through router
    $request = Request::fromGlobals();
    $response = $router->dispatch($request->getMethod(), $request->getRequestTarget());
    $response->send();
});

