<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: Router.php
 * Description: WordPress Update API
 */

namespace App\Core;

use App\Middleware\BlacklistMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\SessionMiddleware;
use FastRoute\Dispatcher;
use FastRoute\ConfigureRoutes;
use FastRoute\FastRoute;
use Psr\Http\Message\ResponseInterface;

class Router
{
    private Dispatcher $dispatcher;
    private BlacklistMiddleware $blacklistMiddleware;
    private SessionMiddleware $sessionMiddleware;
    private CsrfMiddleware $csrfMiddleware;

    /**
     * Build the FastRoute dispatcher and register all application routes.
     */
    public function __construct()
    {
        $this->blacklistMiddleware = new BlacklistMiddleware();
        $this->sessionMiddleware = new SessionMiddleware();
        $this->csrfMiddleware = new CsrfMiddleware();

        $fastRoute = FastRoute::recommendedSettings(function (ConfigureRoutes $r): void {
            $r->addRoute('GET', '/', function (): ResponseManager {
                return ResponseManager::redirect('/home');
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
            $r->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], '/api', ['\\App\\Controllers\\ApiController', 'handleRequest']);
        }, 'app_routes')->disableCache();

        $this->dispatcher = $fastRoute->dispatcher();
    }

    public function dispatch(RequestManager $request): ResponseManager
    {
        ErrorManager::logRequest($request->method, $request->path);

        $middlewareManager = new MiddlewareManager();
        $middlewareManager
            ->add($this->blacklistMiddleware)
            ->add($this->sessionMiddleware)
            ->add($this->csrfMiddleware);

        return $middlewareManager->handle($request, function (RequestManager $request): ResponseManager {
            return $this->dispatchRoute($request);
        });
    }

    private function dispatchRoute(RequestManager $request): ResponseManager
    {
        $method = $request->method;
        $uri = $request->path;

        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            return $this->logAndReturn($method, $uri, ResponseManager::view('404', [], 404));
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->logAndReturn($method, $uri, new ResponseManager(405));
        }

        // FOUND
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        $response = $this->invokeHandler($handler, $vars);
        return $this->logAndReturn($method, $uri, $response);
    }

    /**
     * Invoke a FastRoute handler and normalize the response into ResponseManager.
     *
     * @param mixed $handler
     * @param array<string, string> $vars
     */
    private function invokeHandler(mixed $handler, array $vars): ResponseManager
    {
        $arguments = array_values($vars);

        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            $controller = new $class();
            $response = $controller->{$action}(...$arguments);
            return $this->normalizeResponse($response, 'Controller action must return a ResponseInterface');
        }

        if (is_callable($handler)) {
            $response = $handler(...$arguments);
            return $this->normalizeResponse($response, 'Route callback must return a ResponseInterface');
        }

        throw new \RuntimeException('Invalid route handler');
    }

    /**
     * Normalize a PSR response into ResponseManager.
     */
    private function normalizeResponse(mixed $response, string $errorMessage): ResponseManager
    {
        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException($errorMessage);
        }

        return $response instanceof ResponseManager
            ? $response
            : new ResponseManager($response->getStatusCode(), $response->getHeaders());
    }

    private function logAndReturn(string $method, string $uri, ResponseManager $response): ResponseManager
    {
        ErrorManager::logResponse($method, $uri, $response->getStatusCode());

        return $response;
    }
}
