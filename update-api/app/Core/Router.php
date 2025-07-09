<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Router.php
 * Description: WordPress Update API
 */

namespace App\Core;

use App\Core\AuthMiddleware;

class Router
{
    public function dispatch(string $uri): void
    {
        $route = strtok($uri, '?');

        if ($route !== '/login' && $route !== '/api') {
            AuthMiddleware::check();
        }
        switch ($route) {
            case '/':
                // Redirect the root URL to the home page for convenience
                header('Location: /home');
                exit();
            case '/login':
                \App\Controllers\AuthController::handleRequest();
                break;
            case '/api':
                \App\Controllers\ApiController::handleRequest();
                break;
            case '/plupdate':
                \App\Controllers\PluginsController::handleRequest();
                break;
            case '/thupdate':
                \App\Controllers\ThemesController::handleRequest();
                break;
            case '/logs':
                \App\Controllers\LogsController::handleRequest();
                break;
            case '/home':
                \App\Controllers\HomeController::handleRequest();
                break;
            default:
                header('HTTP/1.0 404 Not Found');
                echo '404 - Page Not Found';
                exit();
        }
    }
}
