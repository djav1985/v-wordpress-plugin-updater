<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\RequestManager;
use App\Core\ResponseManager;
use App\Models\BlacklistModel;

class BlacklistMiddleware
{
    public function __invoke(RequestManager $request, callable $next): ResponseManager
    {
        if (BlacklistModel::isCurrentRequestIpBlacklisted()) {
            return new ResponseManager(403);
        }

        return $next($request);
    }
}
