<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

/**
 * Constrain FastRoute usage to a single factory to simplify future upgrades.
 */
class RouteDispatcherFactory
{
    /**
     * Build a FastRoute dispatcher for the current route table.
     *
     * @param callable(RouteCollector):void $configureRoutes
     */
    public static function build(callable $configureRoutes): Dispatcher
    {
        return simpleDispatcher($configureRoutes);
    }
}
