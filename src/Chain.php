<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use tommyknocker\chain\Exception\ChainException;
use tommyknocker\chain\Exception\ChainInvalidOperationException;
use tommyknocker\chain\Exception\ChainMethodNotFoundException;
use tommyknocker\chain\Traits\ConditionalTrait;
use tommyknocker\chain\Traits\DebuggingTrait;
use tommyknocker\chain\Traits\ErrorHandlingTrait;

/**
 * Chain - Fluent method chaining utility for PHP.
 *
 * This class provides a fluent interface for method chaining across objects,
 * with support for conditional execution, error handling, and functional pipelines.
 *
 * @example
 * $result = Chain::of(new Calculator(10))
 *     ->add(5)
 *     ->when(fn($c) => $c->isPositive(), fn($chain) => $chain->multiply(2))
 *     ->getValue()
 *     ->get();
 *
 * @author Vasiliy Krivoplyas <vasiliy@krivoplyas.com>
 * @since 1.0.0
 */
class Chain
{
    use ErrorHandlingTrait;
    use DebuggingTrait;
    use ConditionalTrait;

    protected object $instance;
    protected mixed $result = null;
    private static ?ContainerInterface $resolver = null;
    private static ?ChainConfig $config = null;
    /** @var array<string, bool> */
    private static array $methodCache = [];
    /** @var array<int, ChainExtensionInterface> */
    private array $extensions = [];

    public static function setResolver(ContainerInterface $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * Configure Chain behavior.
     */
    public static function configure(ChainConfig $config): void
    {
        self::$config = $config;
    }

    /**
     * Get current configuration or default.
     */
    private static function getConfig(): ChainConfig
    {
        return self::$config ?? ChainConfig::default();
    }

    /**
     * Switch current chained instance to a different object (by object or container id).
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

        throw new ChainException("Cannot resolve target: $target");
    }

    private function __construct(object $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Entry point: start a chain for a given object or class.
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
     *
     * @param string $method The method name to call
     * @param array<int, mixed> $args The arguments to pass to the method
     * @throws ChainMethodNotFoundException When method does not exist
     */
    public function __call(string $method, array $args): self
    {
        $class = $this->instance::class;
        $cacheKey = $class . '::' . $method;

        // Check method existence with caching if enabled
        if (self::getConfig()->enableMethodCaching) {
            if (!isset(self::$methodCache[$cacheKey])) {
                self::$methodCache[$cacheKey] = method_exists($this->instance, $method);
            }

            if (!self::$methodCache[$cacheKey]) {
                throw new ChainMethodNotFoundException(
                    sprintf('Method %s::%s() not found.', $class, $method)
                );
            }
        } else {
            if (!method_exists($this->instance, $method)) {
                throw new ChainMethodNotFoundException(
                    sprintf('Method %s::%s() not found.', $class, $method)
                );
            }
        }

        // Call extensions before method call
        foreach ($this->extensions as $extension) {
            $extension->beforeMethodCall($method, $args);
        }

        $result = $this->instance->{$method}(...$args);
        $this->result = $result;

        // Call extensions after method call
        foreach ($this->extensions as $extension) {
            $extension->afterMethodCall($method, $result);
        }

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
            throw new ChainInvalidOperationException('map() must return an object.');
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

    /**
     * Alias for get() - more semantic for value extraction.
     */
    public function value(): mixed
    {
        return $this->get();
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
        $cloned->extensions = []; // Reset extensions for cloned chain

        return $cloned;
    }

    /**
     * Add an extension to the chain.
     */
    public function addExtension(ChainExtensionInterface $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * Iterate over the current value if it's iterable.
     * @param callable $fn Function to execute for each item (receives item and key)
     */
    public function each(callable $fn): self
    {
        $value = $this->result ?? $this->instance;

        if (is_array($value) || $value instanceof \Traversable) {
            foreach ($value as $key => $item) {
                $fn($item, $key);
            }
        }

        return $this;
    }
}
