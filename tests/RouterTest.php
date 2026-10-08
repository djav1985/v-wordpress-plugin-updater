<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\SessionManager;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        unset($_SERVER['REMOTE_ADDR']);
    }

    protected function tearDown(): void
    {
        SessionManager::getInstance()->destroy();
        $_POST = [];
        unset($_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);

        parent::tearDown();
    }

    public function testDispatchReturnsNotFoundForUnknownApiRoute(): void
    {
        $router = new Router();

        $response = $router->dispatch(
            new Request('GET', '/api/missing', ['type' => 'plugin'], [], ['REMOTE_ADDR' => '127.0.0.1'])
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Not Found', $response->getReasonPhrase());
        self::assertSame('404', $response->getView());
    }

    public function testDispatchReturnsMethodNotAllowedForProtectedRoute(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit-agent';
        SessionManager::getInstance()->set('logged_in', true);
        SessionManager::getInstance()->set('user_agent', 'phpunit-agent');
        SessionManager::getInstance()->set('timeout', time());
        SessionManager::getInstance()->set('csrf_token', 'router-test-token');

        $router = new Router();
        $request = new Request(
            'PUT',
            '/home',
            [],
            ['csrf_token' => 'router-test-token'],
            ['HTTP_USER_AGENT' => 'phpunit-agent', 'REMOTE_ADDR' => '127.0.0.1']
        );

        $response = $router->dispatch($request);

        self::assertSame(405, $response->getStatusCode());
        self::assertFalse($response->hasHeader('Location'));
    }

    public function testDispatchInvokesCallableRoute(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit-agent';
        SessionManager::getInstance()->set('logged_in', true);
        SessionManager::getInstance()->set('user_agent', 'phpunit-agent');
        SessionManager::getInstance()->set('timeout', time());

        $router = new Router();
        $request = new Request(
            'GET',
            '/',
            [],
            [],
            ['HTTP_USER_AGENT' => 'phpunit-agent', 'REMOTE_ADDR' => '127.0.0.1']
        );

        $response = $router->dispatch($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testDispatchInvokesControllerRoute(): void
    {
        $router = new Router();

        $response = $router->dispatch(
            new Request('GET', '/login', [], [], ['REMOTE_ADDR' => '127.0.0.1'])
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('login', $response->getView());
    }
}
