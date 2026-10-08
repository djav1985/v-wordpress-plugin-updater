<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\RequestManager;
use App\Core\ResponseManager;
use App\Helpers\ValidationHelper;

class CsrfMiddleware
{
    public function __invoke(RequestManager $request, callable $next): ResponseManager
    {
        if (!in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true) && !str_starts_with($request->path, '/api')) {
            $token = $request->post['csrf_token'] ?? '';
            if (!ValidationHelper::validateCsrfToken(is_string($token) ? $token : '')) {
                return ResponseManager::redirect($request->path);
            }
        }

        return $next($request);
    }
}
