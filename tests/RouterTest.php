<?php

namespace Tests;

// Ensure the autoloader is loaded from the correct location
require_once __DIR__ . '/../update-api/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\Router;

class RouterTest extends TestCase
{
    public function testGetInstanceReturnsSameRouter(): void
    {
        $instance1 = Router::getInstance();
        $instance2 = Router::getInstance();
        $this->assertSame($instance1, $instance2);
    }
}
