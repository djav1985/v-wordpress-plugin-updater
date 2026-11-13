<?php

declare(strict_types=1);

namespace App\Core;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ServerRequestInterface;

/** Factory for PSR-7 server requests. */
final class Request
{
    public static function fromGlobals(): ServerRequestInterface
    {
        return ServerRequest::fromGlobals();
    }

    /**
     * Build a server request for application code and tests.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $parsedBody
     * @param array<string, mixed> $server
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $uploadedFiles
     */
    public static function create(
        string $method,
        string $path,
        array $query = [],
        array $parsedBody = [],
        array $server = [],
        array $cookies = [],
        array $uploadedFiles = []
    ): ServerRequestInterface {
        $request = new ServerRequest($method, $path, [], Utils::streamFor(''), '1.1', $server);
        return $request
            ->withQueryParams($query)
            ->withParsedBody($parsedBody)
            ->withCookieParams($cookies)
            ->withUploadedFiles($uploadedFiles);
    }
}
