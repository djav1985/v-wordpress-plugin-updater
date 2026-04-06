<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: Request.php
 * Description: HTTP request value object implementing PSR-7 ServerRequestInterface
 */

namespace App\Core;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents an HTTP request implementing PSR-7 ServerRequestInterface.
 */
class Request implements ServerRequestInterface
{
    /**
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path Request path (without query string)
     * @param array<string, mixed> $get Query string parameters
     * @param array<string, mixed> $post POST body parameters
     * @param array<string, mixed> $server Server variables ($_SERVER)
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $get,
        public readonly array $post,
        public readonly array $server,
    ) {
    }

    /**
     * Create a Request from global variables.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($path)) {
            $path = '/';
        }

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $path,
            $_GET,
            $_POST,
            $_SERVER,
        );
    }

    // PSR-7 ServerRequestInterface implementation

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): static
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function hasHeader(string $name): bool
    {
        return false;
    }

    public function getHeader(string $name): array
    {
        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return '';
    }

    public function withHeader(string $name, $value): static
    {
        return $this;
    }

    public function withAddedHeader(string $name, $value): static
    {
        return $this;
    }

    public function withoutHeader(string $name): static
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        throw new \RuntimeException('getBody() not implemented');
    }

    public function withBody(StreamInterface $body): static
    {
        return $this;
    }

    public function getRequestTarget(): string
    {
        return $this->path;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        return $this;
    }

    public function getUri(): UriInterface
    {
        throw new \RuntimeException('getUri() not implemented');
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        return $this;
    }

    public function getServerParams(): array
    {
        return $this->server;
    }

    public function getCookieParams(): array
    {
        return [];
    }

    public function withCookieParams(array $cookies): static
    {
        return $this;
    }

    public function getQueryParams(): array
    {
        return $this->get;
    }

    public function withQueryParams(array $query): static
    {
        return $this;
    }

    public function getUploadedFiles(): array
    {
        return [];
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        return $this;
    }

    public function getParsedBody(): mixed
    {
        return $this->post;
    }

    public function withParsedBody(mixed $data): static
    {
        return $this;
    }

    public function getAttributes(): array
    {
        return [];
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $default;
    }

    public function withAttribute(string $name, mixed $value): static
    {
        return $this;
    }

    public function withoutAttribute(string $name): static
    {
        return $this;
    }
}

