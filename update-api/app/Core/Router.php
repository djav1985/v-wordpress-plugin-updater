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

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r): void {
            $r->addRoute(['GET', 'POST'], '/login', ['\\App\\Controllers\\AuthController', 'handleRequest']);
            $r->addRoute(['GET', 'POST'], '/api', ['\\App\\Controllers\\ApiController', 'handleRequest']);
            $r->addRoute(['GET', 'POST'], '/plupdate', ['\\App\\Controllers\\PluginsController', 'handleRequest']);
            $r->addRoute(['GET', 'POST'], '/thupdate', ['\\App\\Controllers\\ThemesController', 'handleRequest']);
            $r->addRoute(['GET', 'POST'], '/logs', ['\\App\\Controllers\\LogsController', 'handleRequest']);
            $r->addRoute(['GET', 'POST'], '/home', ['\\App\\Controllers\\HomeController', 'handleRequest']);
            $r->addRoute('GET', '/', function (): void {
                header('Location: /home');
                exit();
            });
        });
    }

    public function dispatch(string $uri): void
    {
        $route = strtok($uri, '?');
        $routeInfo = $this->dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $route);

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                if (!in_array($route, ['/login', '/api'], true)) {
                    AuthMiddleware::check();
                }
                if (is_array($handler)) {
                    call_user_func($handler);
                } elseif (is_callable($handler)) {
                    $handler();
                }
                break;
            default:
                header('HTTP/1.0 404 Not Found');
                require __DIR__ . '/../Views/404.php';
                exit();
        }
    }
}
