<?php

declare(strict_types=1);

use App\Core\Request;
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
            new Request('GET', '/home', [], [], ['REMOTE_ADDR' => '127.0.0.1'])
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testSessionManagerAcceptsValidSession(): void
    {
        $this->loginSession();

        self::assertTrue(SessionManager::getInstance()->requireAuth());
    }

    public function testSessionManagerRejectsChangedUserAgent(): void
    {
        $this->loginSession();
        $_SERVER['HTTP_USER_AGENT'] = 'other-agent';

        self::assertFalse(SessionManager::getInstance()->isValid());
    }

    public function testEnsureCsrfTokenCreatesToken(): void
    {
        SessionManager::getInstance()->ensureCsrfToken();

        self::assertMatchesRegularExpression(
            '/\A[a-f0-9]{64}\z/',
            SessionManager::getInstance()->get('csrf_token')
        );
    }

    public function testRedirectsWhenCsrfTokenInvalid(): void
    {
        $this->loginSession();
        SessionManager::getInstance()->set('csrf_token', 'expected-token');

        $response = (new Router())->dispatch(
            new Request('POST', '/home', [], ['csrf_token' => 'wrong-token'], ['REMOTE_ADDR' => '127.0.0.1'])
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/home', $response->getHeaderLine('Location'));
    }
}
