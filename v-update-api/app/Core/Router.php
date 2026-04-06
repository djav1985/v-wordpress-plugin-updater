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

use App\Helpers\BlacklistHelper;
use App\Helpers\SessionHelper;
use App\Helpers\ValidationHelper;
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

    public function dispatch(string $method, string $uri): Response
    {
        ErrorManager::logRequest($method, $uri);

        if ($uri !== '/login' && !str_starts_with($uri, '/api')) {
            if (BlacklistHelper::isBlacklisted()) {
                return new Response(403);
            }

            if (!SessionHelper::isValid()) {
                return Response::redirect('/login');
            }
        }

        if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) && !str_starts_with($uri, '/api')) {
            $token = $_POST['csrf_token'] ?? '';
            if (!ValidationHelper::validateCsrfToken(is_string($token) ? $token : '')) {
                return Response::redirect($uri);
            }
        }

        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            $response = Response::view('404', [], 404);
            ErrorManager::logResponse($method, $uri, 404);
            return $response;
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            $response = new Response(405);
            ErrorManager::logResponse($method, $uri, 405);
            return $response;
        }

        // FOUND
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            $controller = new $class();
            $response = call_user_func_array([$controller, $action], $vars);

            if (!$response instanceof ResponseInterface) {
                throw new \RuntimeException('Controller action must return a ResponseInterface');
            }

            $result = $response instanceof Response
                ? $response
                : new Response($response->getStatusCode(), $response->getHeaders());
            ErrorManager::logResponse($method, $uri, $result->getStatusCode());
            return $result;
        }

        if (is_callable($handler)) {
            $response = call_user_func($handler);

            if (!$response instanceof ResponseInterface) {
                throw new \RuntimeException('Route callback must return a ResponseInterface');
            }

            $result = $response instanceof Response
                ? $response
                : new Response($response->getStatusCode(), $response->getHeaders());
            ErrorManager::logResponse($method, $uri, $result->getStatusCode());
            return $result;
        }

        throw new \RuntimeException('Invalid route handler');
    }
}
