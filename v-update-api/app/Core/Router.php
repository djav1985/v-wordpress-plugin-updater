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

use App\Helpers\ValidationHelper;
use App\Models\BlacklistModel;
use FastRoute\Dispatcher;
use FastRoute\ConfigureRoutes;
use FastRoute\FastRoute;
use Psr\Http\Message\ResponseInterface;

class Router
{
    private Dispatcher $dispatcher;

    /**
     * Build the FastRoute dispatcher and register all application routes.
     */
    public function __construct()
    {
        $fastRoute = FastRoute::recommendedSettings(function (ConfigureRoutes $r): void {
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
            $r->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], '/api', ['\\App\\Controllers\\ApiController', 'handleRequest']);
        }, 'app_routes')->disableCache();

        $this->dispatcher = $fastRoute->dispatcher();
    }

    public function dispatch(Request $request): Response
    {
        ErrorManager::logRequest($request->method, $request->path);

        return $this->dispatchRoute($request);
    }

    private function dispatchRoute(Request $request): Response
    {
        $method = $request->method;
        $uri = $request->path;

        $guard = $this->guard($request);
        if ($guard !== null) {
            return $this->logAndReturn($method, $uri, $guard);
        }

        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            return $this->logAndReturn($method, $uri, Response::view('404', [], 404));
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->logAndReturn($method, $uri, new Response(405));
        }

        // FOUND
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        $response = $this->invokeHandler($handler, $vars);
        return $this->logAndReturn($method, $uri, $response);
    }

    /**
     * Blacklist, session and CSRF checks; returns a response to short-circuit, or null to continue.
     */
    private function guard(Request $request): ?Response
    {
        if (BlacklistModel::isCurrentRequestIpBlacklisted()) {
            return new Response(403);
        }

        $session = SessionManager::getInstance();
        $session->ensureCsrfToken();

        $isApi = str_starts_with($request->path, '/api');

        if ($request->path !== '/login' && !$isApi && !$session->requireAuth()) {
            return Response::redirect('/login');
        }

        if (!$isApi && !in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            $token = $request->post['csrf_token'] ?? '';
            if (!ValidationHelper::validateCsrfToken(is_string($token) ? $token : '')) {
                return Response::redirect($request->path);
            }
        }

        return null;
    }

    /**
     * Invoke a FastRoute handler and normalize the response into Response.
     *
     * @param mixed $handler
     * @param array<string, string> $vars
     */
    private function invokeHandler(mixed $handler, array $vars): Response
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
     * Normalize a PSR response into Response.
     */
    private function normalizeResponse(mixed $response, string $errorMessage): Response
    {
        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException($errorMessage);
        }

        return $response instanceof Response
            ? $response
            : new Response($response->getStatusCode(), $response->getHeaders());
    }

    private function logAndReturn(string $method, string $uri, Response $response): Response
    {
        ErrorManager::logResponse($method, $uri, $response->getStatusCode());

        return $response;
    }
}
