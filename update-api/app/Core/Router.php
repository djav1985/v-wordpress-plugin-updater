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
use App\Core\SessionManager;

class Router
{
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r): void {
            $r->addRoute('GET', '/login', ['\\App\\Controllers\\LoginController', 'handleRequest']);
            $r->addRoute('POST', '/login', ['\\App\\Controllers\\LoginController', 'handleSubmission']);
            $r->addRoute('GET', '/home', ['\\App\\Controllers\\HomeController', 'handleRequest']);
            $r->addRoute('POST', '/home', ['\\App\\Controllers\\HomeController', 'handleSubmission']);
            $r->addRoute('GET', '/plupdate', ['\\App\\Controllers\\PluginsController', 'handleRequest']);
            $r->addRoute('POST', '/plupdate', ['\\App\\Controllers\\PluginsController', 'handleSubmission']);
            $r->addRoute('GET', '/thupdate', ['\\App\\Controllers\\ThemesController', 'handleRequest']);
            $r->addRoute('POST', '/thupdate', ['\\App\\Controllers\\ThemesController', 'handleSubmission']);
            $r->addRoute('GET', '/logs', ['\\App\\Controllers\\LogsController', 'handleRequest']);
            $r->addRoute('POST', '/logs', ['\\App\\Controllers\\LogsController', 'handleSubmission']);
            $r->addRoute('GET', '/feeds/{user}/{account}', ['\\App\\Controllers\\FeedsController', 'handleRequest']);
            $r->addRoute('GET', '/api', ['\\App\\Controllers\\ApiController', 'handleRequest']);
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
                $vars = $routeInfo[2] ?? [];
                if ($route !== '/login' && $route !== '/api' && !str_starts_with($route, '/feeds')) {
                    SessionManager::getInstance()->requireAuth();
                }
                if (is_array($handler)) {
                    $controller = new $handler[0]();
                    $method = $handler[1];
                    if (!empty($vars)) {
                        $controller->$method($vars);
                    } else {
                        $controller->$method();
                    }
                } else {
                    call_user_func($handler);
                }
                break;
            default:
                header('HTTP/1.0 404 Not Found');
                require __DIR__ . '/../Views/404.php';
                exit();
        }
    }
}
