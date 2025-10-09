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

    /**
     * @param object $instance
     */
    private function __construct(object $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Chain initialization
     * @param object $instance
     * @return self
     */
    public static function from(object $instance): self
    {
        return new self($instance);
    }

    /***
     * A convenient helper for creating an object by class name and constructor arguments
     * @param string $class
     * @param mixed ...$args
     * @return self
     */
    public static function make(string $class, mixed ...$args): self
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class $class not found.");
        }
        /** @var object $obj */
        $obj = new $class(...$args);
        return new self($obj);
    }

    /**
     * Call a method on the current object, store the result, and return the Chain.
     * @param string $method
     * @param mixed ...$args
     * @return $this
     */
    public function call(string $method, mixed ...$args): self
    {
        if (!method_exists($this->instance, $method)) {
            throw new BadMethodCallException(
                sprintf('Method %s::%s() not found.', $this->instance::class, $method)
            );
        }

        /** @var mixed $result */
        $result = $this->instance->{$method}(...$args);
        $this->result = $result;

        // If the method returns an object, chaining can continue on that object.
        if (is_object($result)) {
            $this->instance = $result;
        }

        return $this;
    }

    /**
     * Direct method call via __call
     * @param string $method
     * @param array $args
     * @return $this
     */
    public function __call(string $method, array $args): self
    {
        return $this->call($method, ...$args);
    }


    /**
     * Apply a function to the current object while keeping the context unchanged.
     * @param callable $fn
     * @return $this
     */
    public function tap(callable $fn): self
    {
        $fn($this->instance);
        return $this;
    }

    /**
     * Transform the current object (the return value from the mapper becomes a new instance).
     * @param callable $fn
     * @return $this
     */
    public function map(callable $fn): self
    {
        $mapped = $fn($this->instance);
        if (!is_object($mapped)) {
            throw new RuntimeException('map() must return an object.');
        }
        $this->instance = $mapped;
        $this->result = null;
        return $this;
    }

    /**
     * Get current object
     * @return object
     */
    public function instance(): object
    {
        return $this->instance;
    }

    /**
     * Get last result
     * @return mixed
     */
    public function result(): mixed
    {
        return $this->result;
    }
}
