<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: UpdateApiRequestTest.php
 * Description: Update API-specific tests for request defaults.
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class UpdateApiRequestTest extends TestCase
{
    public function testFromGlobalsLeavesPathEmptyWhenRequestUriIsMissing(): void
    {
        $originalServer = $_SERVER;

        try {
            unset($_SERVER['REQUEST_URI']);
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $request = Request::fromGlobals();

            self::assertSame('GET', $request->getMethod());
            self::assertSame('', $request->getUri()->getPath());
        } finally {
            $_SERVER = $originalServer;
        }
    }
}
