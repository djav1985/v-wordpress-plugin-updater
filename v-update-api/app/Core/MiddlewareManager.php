<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Middleware pipeline manager for request/response flow.
 */
class MiddlewareManager
{
    /** @var array<int, callable(RequestManager, callable(RequestManager): ResponseManager): ResponseManager> */
    private array $middleware = [];

    /**
     * Register middleware to the pipeline.
     *
     * @param callable(RequestManager, callable(RequestManager): ResponseManager): ResponseManager $middleware
     */
    public function add(callable $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Execute middleware stack and final handler.
     *
     * @param callable(RequestManager): ResponseManager $finalHandler
     */
    public function handle(RequestManager $request, callable $finalHandler): ResponseManager
    {
        $runner = array_reduce(
            array_reverse($this->middleware),
            static function (callable $next, callable $middleware): callable {
                return static function (RequestManager $request) use ($middleware, $next): ResponseManager {
                    $response = $middleware($request, $next);
                    if (!$response instanceof ResponseManager) {
                        throw new RuntimeException('Middleware must return an instance of ResponseManager');
                    }

                    return $response;
                };
            },
            static function (RequestManager $request) use ($finalHandler): ResponseManager {
                $response = $finalHandler($request);
                if (!$response instanceof ResponseManager) {
                    throw new RuntimeException('Final handler must return an instance of ResponseManager');
                }

                return $response;
            }
        );

        return $runner($request);
    }

    /**
     * Return registered middleware stack.
     *
     * @return array<int, callable(RequestManager, callable(RequestManager): ResponseManager): ResponseManager>
     */
    public function all(): array
    {
        return $this->middleware;
    }
}
