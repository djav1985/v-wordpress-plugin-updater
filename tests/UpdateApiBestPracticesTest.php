<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Helpers\ValidationHelper;
use App\Helpers\RequestInput;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateApiBestPracticesTest extends TestCase
{
    public function testPasswordValidationPreservesSubmittedWhitespace(): void
    {
        self::assertSame(' secret ', ValidationHelper::validatePassword(' secret '));
    }

    public function testRequestFactoryBuildsACompletePsr7ServerRequest(): void
    {
        $request = Request::create('POST', '/submit', ['page' => '2'], ['name' => 'Ada']);

        self::assertInstanceOf(ServerRequestInterface::class, $request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/submit', $request->getUri()->getPath());
        self::assertSame(['page' => '2'], $request->getQueryParams());
        self::assertSame(['name' => 'Ada'], $request->getParsedBody());
        self::assertSame('Grace', $request->withParsedBody(['name' => 'Grace'])->getParsedBody()['name']);
        self::assertSame('Ada', $request->getParsedBody()['name']);
    }

    public function testMalformedStringParameterIsRejectedWithoutCoercion(): void
    {
        self::assertNull(RequestInput::string(['domain' => ['example.com']], 'domain'));
        self::assertNull(RequestInput::string([], 'domain'));
        self::assertSame('example.com', RequestInput::string(['domain' => 'example.com'], 'domain'));
    }

    public function testResponseExposesAStreamAndImmutablePsr7Updates(): void
    {
        $response = new Response(200, ['X-Test' => 'old'], 'body');
        $updated = $response->withHeader('X-Test', 'new');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame('body', (string) $response->getBody());
        self::assertSame('old', $response->getHeaderLine('X-Test'));
        self::assertSame('new', $updated->getHeaderLine('X-Test'));
    }
}

