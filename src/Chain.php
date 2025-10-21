<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use BadMethodCallException;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class Chain
{
    protected object $instance;
    protected mixed $result = null;
    private static ?ContainerInterface $resolver = null;

    public static function setResolver(ContainerInterface $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * Switch current chained instance to a different object (by object or container id)
     * @param string|object $target
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    public function change(string|object $target): self
    {
        if (is_object($target)) {
            $this->instance = $target;
            return $this;
        }

        if (self::$resolver && self::$resolver->has($target)) {
            $this->instance = self::$resolver->get($target);
            return $this;
        }

        throw new RuntimeException("Cannot resolve target: $target");
    }

    private function __construct(object $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Entry point: start a chain for a given object or class
     * @param string|object $target Class name to instantiate or existing object instance
     * @param mixed ...$args Constructor arguments (only used when $target is a class name)
     */
    public static function of(string|object $target, mixed ...$args): self
    {
        if (is_string($target)) {
            if (!class_exists($target)) {
                throw new InvalidArgumentException("Class $target not found.");
            }
            $target = new $target(...$args);
        }

        return new self($target);
    }

    /**
     * Magic method to call methods on the wrapped instance.
     * @param array<int, mixed> $args
     */
    public function __call(string $method, array $args): self
    {
        if (!method_exists($this->instance, $method)) {
            throw new BadMethodCallException(
                sprintf('Method %s::%s() not found.', $this->instance::class, $method)
            );
        }

        $result = $this->instance->{$method}(...$args);
        $this->result = $result;
        if (is_object($result)) {
            $this->instance = $result; // chain can continue on returned object
        }
        return $this;
    }


    /** Execute a side‑effect without altering the current instance reference */
    public function tap(callable $fn): self
    {
        $fn($this->instance);
        return $this;
    }

    /** Replace the current instance with the object returned by mapper */
    public function map(callable $fn): self
    {
        $mapped = $fn($this->instance);
        if (!is_object($mapped)) {
            throw new RuntimeException('map() must return an object.');
        }
        $this->instance = $mapped;
        $this->result = null; // reset last scalar result
        return $this;
    }

    /** Current wrapped object */
    public function instance(): object
    {
        return $this->instance;
    }

    /**
     * Get last scalar/object-returning method call result if available, otherwise the current instance.
     */
    public function get(): mixed
    {
        return $this->result ?? $this->instance;
    }

    /** Conditional execution */
    public function when(bool|callable $condition, callable $callback, ?callable $default = null): self
    {
        $conditionResult = is_callable($condition) ? $condition($this->instance) : $condition;
        if ($conditionResult) {
            $callback($this);
        } elseif ($default !== null) {
            $default($this);
        }
        return $this;
    }

    /** Inverse conditional */
    public function unless(bool|callable $condition, callable $callback, ?callable $default = null): self
    {
        $conditionResult = is_callable($condition) ? $condition($this->instance) : $condition;
        return $this->when(!$conditionResult, $callback, $default);
    }

    /** Functional pipeline over successive results */
    public function pipe(callable ...$pipes): self
    {
        $current = $this->instance;
        foreach ($pipes as $pipe) {
            $result = $pipe($current);
            $this->result = $result;
            $current = $result;
            if (is_object($result)) {
                $this->instance = $result; // update context only if object
            }
        }
        return $this;
    }

    /** Clone chain & underlying instance (shallow) for immutable branching */
    public function clone(): self
    {
        $cloned = clone $this;
        $cloned->instance = clone $this->instance;
        return $cloned;
    }
}
