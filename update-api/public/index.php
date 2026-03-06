<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: index.php
 * Description: WordPress Update API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\ErrorManager;
use App\Core\SessionManager;
use function random_bytes;

$session = SessionManager::getInstance();
$session->start();
if (!$session->get('csrf_token')) {
    $session->set('csrf_token', bin2hex(random_bytes(32)));
}

ErrorManager::handle(function (): void {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    Router::getInstance()->dispatch($_SERVER['REQUEST_METHOD'], $uri);
});
