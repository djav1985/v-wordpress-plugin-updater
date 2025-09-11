<?php

namespace App\Core;

/**
 * Simple HTTP response representation.
 *
 * @phpstan-type Headers array<string,string>
 */
class Response
{
    /**
     * @param int            $status  HTTP status code
     * @param Headers        $headers Response headers
     * @param string         $body    Response body
     * @param string|null    $file    Path to file to stream
     * @param string|null    $view    View name to render
     * @param array<string,mixed> $data    Data passed to view
     */
    public function __construct(
        public int $status = 200,
        public array $headers = [],
        public string $body = '',
        public ?string $file = null,
        public ?string $view = null,
        public array $data = []
    ) {
    }

    /**
     * Create redirect response.
     */
    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, ['Location' => $location]);
    }

    /**
     * Create view response.
     *
     * @param array<string,mixed> $data
     */
    public static function view(string $view, array $data = [], int $status = 200): self
    {
        return new self($status, [], '', null, $view, $data);
    }

    /**
     * Create plain text response.
     */
    public static function text(string $body, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/plain'], $body);
    }

    /**
     * Create file streaming response.
     */
    public static function file(string $path, array $headers = [], int $status = 200): self
    {
        return new self($status, $headers, '', $path);
    }
}
