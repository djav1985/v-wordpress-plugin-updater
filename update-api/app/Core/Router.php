<?php
namespace App\Core;

class Router
{
    public function dispatch(string $uri): void
    {
        $route = strtok($uri, '?');
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
