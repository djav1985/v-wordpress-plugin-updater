<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: RouterGuardTest.php
 * Description: PHPUnit tests for RouterGuard.
 */

declare(strict_types=1);

use Tests\Support\SharedRequestFactory;
use App\Core\Router;
use App\Core\SessionManager;
use PHPUnit\Framework\TestCase;

final class RouterGuardTest extends TestCase
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

    private function loginSession(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit-agent';
        SessionManager::getInstance()->set('logged_in', true);
        SessionManager::getInstance()->set('user_agent', 'phpunit-agent');
        SessionManager::getInstance()->set('timeout', time());
    }

    public function testRedirectsToLoginWhenSessionInvalid(): void
    {
        SessionManager::getInstance()->destroy();

        $response = (new Router())->dispatch(
            SharedRequestFactory::create('GET', '/home', [], [], [])
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testRedirectsWhenCsrfTokenInvalid(): void
    {
        $this->loginSession();
        SessionManager::getInstance()->set('csrf_token', 'expected-token');

        $response = (new Router())->dispatch(
            SharedRequestFactory::create('POST', '/home', [], ['csrf_token' => 'wrong-token'], ['HTTP_USER_AGENT' => 'phpunit-agent'])
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testRedirectsWhenCsrfTokenIsAnArray(): void
    {
        $this->loginSession();
        SessionManager::getInstance()->set('csrf_token', 'expected-token');

        $response = (new Router())->dispatch(
            SharedRequestFactory::create('POST', '/home', [], ['csrf_token' => ['unexpected']], ['HTTP_USER_AGENT' => 'phpunit-agent'])
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/home', $response->getHeaderLine('Location'));
    }
}
