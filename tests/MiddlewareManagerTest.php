<?php

declare(strict_types=1);

use App\Core\MiddlewareManager;
use App\Core\RequestManager;
use App\Core\ResponseManager;
use PHPUnit\Framework\TestCase;

final class MiddlewareManagerTest extends TestCase
{
    public function testPipelineRunsInExpectedOrder(): void
    {
        $trace = [];
        $manager = new MiddlewareManager();

        $manager->add(static function (RequestManager $request, callable $next) use (&$trace): ResponseManager {
            $trace[] = 'first:before';
            $response = $next($request);
            $trace[] = 'first:after';

            return $response->withAddedHeader('X-First', '1');
        });

        $manager->add(static function (RequestManager $request, callable $next) use (&$trace): ResponseManager {
            $trace[] = 'second:before';
            $response = $next($request);
            $trace[] = 'second:after';

            return $response->withAddedHeader('X-Second', '1');
        });

        $request = new RequestManager('GET', '/health', [], [], []);

        $response = $manager->handle(
            $request,
            static function (RequestManager $request) use (&$trace): ResponseManager {
                $trace[] = 'handler';

                return ResponseManager::text('ok');
            }
        );

        self::assertSame(
            ['first:before', 'second:before', 'handler', 'second:after', 'first:after'],
            $trace
        );
        self::assertTrue($response->hasHeader('X-First'));
        self::assertTrue($response->hasHeader('X-Second'));
    }

    public function testThrowsWhenMiddlewareReturnsInvalidValue(): void
    {
        $manager = new MiddlewareManager();
        $manager->add(static function (RequestManager $request, callable $next): string {
            return 'nope';
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Middleware must return an instance of ResponseManager');

        $manager->handle(
            new RequestManager('GET', '/health', [], [], []),
            static function (RequestManager $request): ResponseManager {
                return ResponseManager::text('ok');
            }
        );
    }
}
