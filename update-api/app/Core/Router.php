<?php

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

        if ($route !== '/login') {
            AuthMiddleware::check();
        }
        switch ($route) {
            case '/login':
                \App\Controllers\AuthController::handleRequest();
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
            case '/':
            case '/home':
            default:
                \App\Controllers\HomeController::handleRequest();
        }
    }
}
