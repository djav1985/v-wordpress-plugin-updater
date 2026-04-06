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

use FastRoute\Dispatcher;
use FastRoute\ConfigureRoutes;
use function FastRoute\simpleDispatcher;

class Router
{
    private static ?Router $instance = null;
    private Dispatcher $dispatcher;

    /**
     * Build the FastRoute dispatcher and register all application routes.
     */
    private function __construct()
    {
        $this->dispatcher = simpleDispatcher(function (ConfigureRoutes $r): void {
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
        });
    }

    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function dispatch(string $method, string $uri): void
    {
        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $this->sendResponse(ResponseManager::view('404', [], 404));
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $this->sendResponse(new ResponseManager(405));
                break;

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;
                    $isApi = str_starts_with($uri, '/api');

                    if ($uri !== '/login' && !$isApi) {
                        if (!SessionManager::getInstance()->requireAuth()) {
                            $this->sendResponse(ResponseManager::redirect('/login'));
                            return;
                        }
                    }

                    $result = call_user_func_array([new $class(), $action], $vars);
                    if ($result instanceof ResponseManager) {
                        $this->sendResponse($result);
                    }
                } elseif (is_callable($handler)) {
                    $result = call_user_func($handler);
                    if ($result instanceof ResponseManager) {
                        $this->sendResponse($result);
                    }
                }
                break;
        }
    }

    private function sendResponse(ResponseManager $response): void
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

        if ($response->getFile() !== null) {
            $file = $response->getFile();
            if (!is_string($file) || !is_file($file) || !is_readable($file)) {
                ErrorManager::getInstance()->log('Router refused unreadable file response: ' . (string) $file);
                ResponseManager::text('Internal Server Error', 500)->send();
                return;
            }
        }

        $response->send();
    }
}
