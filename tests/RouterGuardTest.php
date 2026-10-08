<?php

declare(strict_types=1);

use App\Core\RequestManager;
use App\Core\ResponseManager;
use App\Core\SessionManager;
use App\Middleware\CsrfMiddleware;
use App\Middleware\SessionMiddleware;
use PHPUnit\Framework\TestCase;

final class MiddlewareBehaviorTest extends TestCase
{
    protected function tearDown(): void
    {
        SessionManager::getInstance()->destroy();
        $_POST = [];
        unset($_SERVER['HTTP_USER_AGENT']);

        parent::tearDown();
    }

    public function testSessionMiddlewareRedirectsWhenSessionInvalid(): void
    {
        SessionManager::getInstance()->destroy();

        $middleware = new SessionMiddleware();
        $request = new RequestManager('GET', '/home', [], [], []);

        $response = $middleware(
            $request,
            static function (RequestManager $request): ResponseManager {
                return ResponseManager::text('ok');
            }
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testSessionMiddlewareAllowsValidSession(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit-agent';
        SessionManager::getInstance()->set('logged_in', true);
        SessionManager::getInstance()->set('user_agent', 'phpunit-agent');
        SessionManager::getInstance()->set('timeout', time());

        $middleware = new SessionMiddleware();
        $request = new RequestManager('GET', '/home', [], [], []);

        $response = $middleware(
            $request,
            static function (RequestManager $request): ResponseManager {
                return ResponseManager::text('ok');
            }
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getBodyAsString());
    }

    public function testCsrfMiddlewareRedirectsWhenTokenInvalid(): void
    {
        SessionManager::getInstance()->set('csrf_token', 'expected-token');

        $middleware = new CsrfMiddleware();
        $request = new RequestManager('POST', '/home', [], ['csrf_token' => 'wrong-token'], []);

        $response = $middleware(
            $request,
            static function (RequestManager $request): ResponseManager {
                return ResponseManager::text('ok');
            }
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testCsrfMiddlewareAllowsValidToken(): void
    {
        SessionManager::getInstance()->set('csrf_token', 'expected-token');

        $middleware = new CsrfMiddleware();
        $request = new RequestManager('POST', '/home', [], ['csrf_token' => 'expected-token'], []);

        $response = $middleware(
            $request,
            static function (RequestManager $request): ResponseManager {
                return ResponseManager::text('ok');
            }
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getBodyAsString());
    }
}
