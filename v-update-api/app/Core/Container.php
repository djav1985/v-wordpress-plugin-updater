<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: Container.php
 * Description: Lightweight service container for dependency injection
 */

namespace App\Core;

use ReflectionClass;
use ReflectionException;

class Container
{
    /**
     * Registered service factories: id => callable
     *
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * Cached singleton instances: id => instance
     *
     * @var array<string, mixed>
     */
    private array $singletons = [];

    /**
     * Service IDs registered as singletons (cached after first call)
     *
     * @var array<string, true>
     */
    private array $isSingleton = [];

    /**
     * Register a service factory.
     *
     * @param string   $id      Service identifier (e.g., ClassName::class)
     * @param callable $factory Factory callable that returns the service instance
     * @return void
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->singletons[$id]);
        unset($this->isSingleton[$id]);
    }

    /**
     * Register a singleton service factory.
     * The factory is called once; subsequent calls return the cached instance.
     *
     * @param string   $id      Service identifier
     * @param callable $factory Factory callable that returns the service instance
     * @return void
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        $this->isSingleton[$id] = true;
        unset($this->singletons[$id]);
    }

    /**
     * Retrieve or create a service.
     *
     * @param string $id Service identifier
     * @return mixed The service instance
     * @throws \RuntimeException When the service is not registered
     */
    public function get(string $id): mixed
    {
        if (!isset($this->factories[$id])) {
            throw new \RuntimeException("Service not registered: $id");
        }

        if (isset($this->isSingleton[$id])) {
            if (!isset($this->singletons[$id])) {
                $this->singletons[$id] = ($this->factories[$id])($this);
            }
            return $this->singletons[$id];
        }

        return ($this->factories[$id])($this);
    }

    /**
     * Instantiate a class with auto-wiring based on constructor type hints.
     *
     * Uses reflection to inspect the constructor and inject dependencies from
     * the container based on parameter type hints.
     *
     * @param string $class Fully qualified class name
     * @return object An instance of the class
     * @throws ReflectionException When the class cannot be reflected
     * @throws \RuntimeException When a dependency cannot be resolved
     */
    public function make(string $class): object
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Cannot reflect class '$class': " . $e->getMessage());
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $args = [];

        foreach ($parameters as $param) {
            $type = $param->getType();
            if ($type === null) {
                throw new \RuntimeException(
                    "Cannot auto-wire parameter '\${$param->getName()}' in {$class}: no type hint"
                );
            }

            $typeName = (string) $type;
            if (!isset($this->factories[$typeName])) {
                throw new \RuntimeException(
                    "Cannot auto-wire parameter '\${$param->getName()}' of type {$typeName} in {$class}: "
                    . "service not registered"
                );
            }

            $args[] = $this->get($typeName);
        }

        return new $class(...$args);
    }
}
