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

    /**
     * Build the FastRoute dispatcher and register all application routes.
     */
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

    /**
     * Dispatches the request to the appropriate controller action.
     *
     * The caller (e.g., index.php) is responsible for parsing the URI path
     * from REQUEST_URI and passing only the path component (no query string).
     *
     * If a controller action returns a Response instance the Router emits it
     * via sendResponse(). Actions that handle output themselves (header/echo/exit)
     * continue to work unchanged.
     *
     * @param string $method HTTP method of the incoming request.
     * @param string $uri    The requested URI path (query string should be pre-parsed by caller).
     */
    public function dispatch(string $method, string $uri): void
    {
        // Router receives already-parsed path; use as-is.
        $route = $uri;

        $routeInfo = $this->dispatcher->dispatch($method, $route);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $this->sendResponse(Response::view('404', [], 404));
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $this->sendResponse(new Response(405));
                break;

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars    = $routeInfo[2];

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;

                    // API routes are publicly accessible if they have all required params;
                    // everything else requires authentication.
                    $isApi = str_starts_with($route, '/api');
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

                    $result = call_user_func_array([new $class(), $action], $vars);
                    if ($result instanceof Response) {
                        $this->sendResponse($result);
                    }
                } elseif (is_callable($handler)) {
                    $result = call_user_func($handler);
                    if ($result instanceof Response) {
                        $this->sendResponse($result);
                    }
                }
                break;
        }
    }

    /**
     * Emit a Response to the client.
     *
     * Handles three output modes in order of priority:
     *  1. View  — requires the named view file and extracts view data into scope.
     *  2. File  — delegates to Response::send() which calls readfile().
     *  3. Body  — delegates to Response::send() which echoes the body string.
     *
     * @param Response $response The response to emit.
     */
    private function sendResponse(Response $response): void
    {
        if ($response->getView() !== null) {
            if (!headers_sent()) {
                http_response_code($response->getStatusCode());

                foreach ($response->getHeaders() as $name => $values) {
                    $replace = true;
                    foreach ($values as $value) {
                        header($name . ': ' . $value, $replace);
                        $replace = false;
                    }
                }
            }

            $data = $response->getViewData();
            if (is_array($data)) {
                extract($data, EXTR_SKIP);
            }

            $view = $response->getView();
            if (!is_string($view) || !preg_match('/^[A-Za-z0-9_\/-]+$/', $view) || str_contains($view, '..')) {
                throw new \RuntimeException('Invalid view name');
            }

            require __DIR__ . '/../Views/' . $view . '.php';
            return;
        }

        // File streaming and plain body output are both handled by send().
        $response->send();
    }
}
