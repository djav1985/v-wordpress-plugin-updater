<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\SessionManager;

class SessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('SESSION_TIMEOUT_LIMIT')) {
            define('SESSION_TIMEOUT_LIMIT', 1800);
        }
        if (!defined('BLACKLIST_DIR')) {
            define('BLACKLIST_DIR', __DIR__ . '/../storage');
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public function testTimeoutExpiryInvalidatesSession(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';
        $session = SessionManager::getInstance();
        $session->start();
        $session->set('logged_in', true);
        $session->set('user_agent', 'Agent');
        $session->set('timeout', time() - (SESSION_TIMEOUT_LIMIT + 1));

        $this->assertFalse($session->isValid());
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testUserAgentChangeInvalidatesSession(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Agent2';
        $session = SessionManager::getInstance();
        $session->start();
        $session->set('logged_in', true);
        $session->set('user_agent', 'Agent1');
        $session->set('timeout', time());

        $this->assertFalse($session->isValid());
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testRequireAuthBlocksBlacklistedIp(): void
    {
        $ip = '10.0.0.1';
        $_SERVER['REMOTE_ADDR'] = $ip;
        $_SERVER['HTTP_USER_AGENT'] = 'Agent';

        $blacklistFile = BLACKLIST_DIR . '/BLACKLIST.json';
        $original = file_exists($blacklistFile) ? file_get_contents($blacklistFile) : '';
        $data = $original ? json_decode($original, true) : [];
        $data[$ip] = [
            'login_attempts' => 3,
            'blacklisted' => true,
            'timestamp' => time(),
        ];
        file_put_contents($blacklistFile, json_encode($data));

        $logFile = __DIR__ . '/../storage/logs/php_app.log';

        $session = SessionManager::getInstance();
        $session->requireAuth();

        $this->assertSame(403, http_response_code());
        $logAfter = file_get_contents($logFile);
        $this->assertStringContainsString($ip, $logAfter);

        file_put_contents($blacklistFile, $original);
    }
}
