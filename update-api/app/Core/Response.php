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

    /**
     * Create JSON response.
     *
     * @param mixed $data
     */
    public static function json(mixed $data, int $status = 200): self
    {
        try {
            $json = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_INVALID_UTF8_SUBSTITUTE);

            return new self($status, ['Content-Type' => 'application/json'], $json);
        } catch (\JsonException $e) {
            // Log the encoding failure and return a safe 500 response.
            if (class_exists(ErrorManager::class)) {
                ErrorManager::getInstance()->log(
                    'JSON encoding failed in Response::json(): ' . $e->getMessage(),
                    'error'
                );
            }

            return new self(
                500,
                ['Content-Type' => 'text/plain'],
                'Internal Server Error'
            );
        }
    }
}
