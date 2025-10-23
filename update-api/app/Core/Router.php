<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: Router.php
 * Description: WordPress Update API
 */

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Core\SessionManager;
use App\Core\Response;

class Router
{
    private static ?Router $instance = null;
    private Dispatcher $dispatcher;

    private function __construct()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r): void {
            // Redirect the root URL to the home page for convenience
            $r->addRoute('GET', '/', function (): Response {
                return Response::redirect('/home');
            });
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
            $r->addRoute('GET', '/sitelogs', ['\\App\\Controllers\\SiteLogsController', 'handleRequest']);
            $r->addRoute('POST', '/sitelogs', ['\\App\\Controllers\\SiteLogsController', 'handleSubmission']);
                $r->addRoute('GET', '/api', ['\\App\\Controllers\\ApiController', 'handleRequest']);
        });
    }

    /**
     * Returns the shared Router instance.
     */
    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function dispatch(string $method, string $uri): void
    {
        $route = strtok($uri, '?');
        $routeInfo = $this->dispatcher->dispatch($method, $route);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                header('HTTP/1.0 404 Not Found');
                require __DIR__ . '/../Views/404.php';
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                header('HTTP/1.0 405 Method Not Allowed');
                break;
            case Dispatcher::FOUND:
                if (is_array($routeInfo[1])) {
                    [$class, $action] = $routeInfo[1];
                    $vars = $routeInfo[2];
                    $isApi = function_exists('str_starts_with')
                        ? str_starts_with($route, '/api')
                        : strpos($route, '/api') === 0;
                    if ($isApi) {
                        $query = parse_url($uri, PHP_URL_QUERY);
                        parse_str($query ?? '', $params);
                        $required = ['type', 'domain', 'key', 'slug', 'version'];
                        foreach ($required as $key) {
                            if (!isset($params[$key])) {
                                $isApi = false;
                                break;
                            }
                        }
                    }
                    if ($route !== '/login' && !$isApi) {
                        if (!SessionManager::getInstance()->requireAuth()) {
                            $this->sendResponse(Response::redirect('/login'));
                            return;
                        }
                    }
                    $response = call_user_func_array([new $class(), $action], $vars);
                    if ($response instanceof Response) {
                        $this->sendResponse($response);
                    }
                } elseif (is_callable($routeInfo[1])) {
                    $response = call_user_func($routeInfo[1]);
                    if ($response instanceof Response) {
                        $this->sendResponse($response);
                    }
                }
                break;
        }
    }

    private function sendResponse(Response $response): void
    {
        http_response_code($response->status);
        // In CLI (when running php -r in tests) headers() do not output to stdout.
        // Echo header lines in CLI so tests that run the app as a subprocess can capture them.
        foreach ($response->headers as $name => $value) {
            // Call header() (unqualified) so tests that define a namespaced
            // header() function can intercept it.
            header($name . ': ' . $value);
        }

        if ($response->file !== null) {
            readfile($response->file);
            return;
        }

        if ($response->view !== null) {
            extract($response->data);
            require __DIR__ . '/../Views/' . $response->view . '.php';
            return;
        }

        echo $response->body;
    }
}
