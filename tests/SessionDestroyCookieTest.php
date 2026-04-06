<?php

namespace Tests;

require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class SessionDestroyCookieTest extends TestCase
{
    private function runScript(string $code): array
    {
        $base = dirname(__DIR__);
        $cmd = 'cd ' . escapeshellarg($base) . ' && php -r ' . escapeshellarg($code);
        $output = [];
        $exit = 0;
        exec($cmd, $output, $exit);
        return [$output, $exit];
    }

    public function testDestroyExpiresSessionCookieAndClosesSession(): void
    {
        $code = <<<'PHP'
namespace App\Core {
    function setcookie(string $name, string $value = "", array $options = []): bool {
        echo json_encode(['name' => $name, 'value' => $value, 'options' => $options]);
        return true;
    }
}
namespace {
    require 'update-api/vendor/autoload.php';
    $_SERVER['HTTP_USER_AGENT'] = 'DestroyCookieTestAgent';
    $session = App\Core\SessionManager::getInstance();
    $session->start();
    $session->set('logged_in', true);
    $session->destroy();
}
PHP;
        [$output, $exit] = $this->runScript($code);
        $this->assertSame(0, $exit);

        $json = implode('', $output);
        $payload = json_decode($json, true);
        $this->assertIsArray($payload);
        $this->assertSame('PHPSESSID', $payload['name'] ?? null);
        $this->assertSame('', $payload['value'] ?? null);
        $this->assertSame('/', $payload['options']['path'] ?? null);
        $this->assertSame('Lax', $payload['options']['samesite'] ?? null);
        $this->assertTrue((bool) ($payload['options']['httponly'] ?? false));
    }
}
