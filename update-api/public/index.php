<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: index.php
 * Description: WordPress Update API
 */

use App\Core\Router;
use App\Core\ErrorManager;
use App\Core\SessionManager;

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$session = SessionManager::getInstance();
$session->start();
$session->regenerate();

ErrorManager::handle(function (): void {
    $router = Router::getInstance();
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
});
