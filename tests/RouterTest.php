<?php

namespace Tests;

// Ensure the autoloader is loaded from the correct location
require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\Router;

class RouterTest extends TestCase
{
    private function runScript(string $code): array
    {
        $base = dirname(__DIR__);
        $cmd  = 'cd ' . escapeshellarg($base) . ' && php -r ' . escapeshellarg($code);
        $output = [];
        $exit   = 0;
        exec($cmd, $output, $exit);
        return [$output, $exit];
    }

    public function testGetInstanceReturnsSameRouter(): void
    {
        $instance1 = Router::getInstance();
        $instance2 = Router::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    public function testRedirectRoot(): void
    {
        $code = <<<'PHP'
namespace App\Core { function header($h){ echo $h; throw new \Exception(); } }
namespace { require 'tests/DummyControllers.php'; require 'update-api/vendor/autoload.php'; try { App\Core\Router::getInstance()->dispatch('GET', '/'); } catch (\Exception $e) {} }
PHP;
        [$out] = $this->runScript($code);
        $this->assertSame('Location: /home', $out[0] ?? '');
    }

    public function testNotFoundRoute(): void
    {
        $code = <<<'PHP'
namespace App\Core { function header($h){ echo $h; throw new \Exception(); } }
namespace { require 'tests/DummyControllers.php'; require 'update-api/vendor/autoload.php'; try { App\Core\Router::getInstance()->dispatch('GET', '/missing'); } catch (\Exception $e) {} }
PHP;
        [$out] = $this->runScript($code);
        $this->assertSame('HTTP/1.0 404 Not Found', $out[0] ?? '');
    }

    public function testMethodNotAllowed(): void
    {
        $code = <<<'PHP'
namespace App\Core { function header($h){ echo $h; throw new \Exception(); } }
namespace { require 'tests/DummyControllers.php'; require 'update-api/vendor/autoload.php'; try { App\Core\Router::getInstance()->dispatch('DELETE', '/home'); } catch (\Exception $e) {} }
PHP;
        [$out] = $this->runScript($code);
        $this->assertSame('HTTP/1.0 405 Method Not Allowed', $out[0] ?? '');
    }

    public function testDispatchesRouteHandler(): void
    {
        $code = <<<'PHP'
namespace App\Core { class SessionManager { public static function getInstance(){ return new self(); } public function requireAuth(){ return true; } } }
namespace { require 'tests/DummyControllers.php'; require 'update-api/vendor/autoload.php'; ob_start(); App\Core\Router::getInstance()->dispatch('GET', '/home'); echo ob_get_clean(); }
PHP;
        [$out] = $this->runScript($code);
        $this->assertStringContainsString('home', implode('', $out));
    }

    public function testApiRouteMissingParamsRequiresAuth(): void
    {
        $code = <<<'PHP'
namespace App\Core { class SessionManager { public static int $count = 0; public static function getInstance(){ return new self(); } public function requireAuth(){ self::$count++; return true; } } }
namespace { require 'tests/DummyControllers.php'; require 'update-api/vendor/autoload.php'; ob_start(); App\Core\Router::getInstance()->dispatch('GET', '/api?type=plugin&domain=example.com'); ob_end_clean(); echo App\Core\SessionManager::$count; }
PHP;
        [$out] = $this->runScript($code);
        $this->assertGreaterThanOrEqual(1, (int)($out[0] ?? 0));
    }
}
