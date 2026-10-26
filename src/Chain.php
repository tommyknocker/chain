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
    protected bool $hasResult = false;
    private static ?ContainerInterface $defaultResolver = null;
    private static ?ChainConfig $defaultConfig = null;
    private static bool $timeoutActive = false;
    private ?ContainerInterface $resolver;
    private ChainConfig $config;
    /** @var array<string, bool> */
    private static array $methodCache = [];
    /** @var array<int, ChainExtensionInterface> */
    private array $extensions = [];

    /** Set the resolver default for chains created after this call. */
    public static function setResolver(?ContainerInterface $resolver): void
    {
        self::$defaultResolver = $resolver;
    }

    /**
     * Set the configuration default for chains created after this call.
     */
    public static function configure(ChainConfig $config): void
    {
        self::$defaultConfig = $config;
    }

    /** Override configuration for this chain only. */
    public function withConfig(ChainConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    /** Override the resolver for this chain only. */
    public function withResolver(?ContainerInterface $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
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
            $this->replaceCurrentObject($target);

            return $this;
        }

        if ($this->resolver && $this->resolver->has($target)) {
            $this->replaceCurrentObject($this->resolver->get($target));

            return $this;
        }

        throw new ChainException("Cannot resolve target: $target");
    }

    private function __construct(object $instance, ChainConfig $config, ?ContainerInterface $resolver)
    {
        $this->instance = $instance;
        $this->config = $config;
        $this->resolver = $resolver;
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

        return new self($target, self::$defaultConfig ?? ChainConfig::default(), self::$defaultResolver);
    }

    /**
     * Magic method to call methods on the wrapped instance.
     *
     * @param string $method The method name to call
     * @param array<int|string, mixed> $args The arguments to pass to the method
     * @throws ChainMethodNotFoundException When method does not exist
     */
    public function __call(string $method, array $args): self
    {
        return $this->invokeMethod($method, $args);
    }

    /**
     * Invoke a method on the wrapped object, including one whose name conflicts with Chain's API.
     *
     * @param string $method The wrapped-object method name
     * @param mixed ...$args Arguments passed to the wrapped-object method
     * @return $this
     * @throws ChainMethodNotFoundException When the method is not callable
     */
    public function invoke(string $method, mixed ...$args): self
    {
        return $this->invokeMethod($method, $args);
    }

    /**
     * @param string $method The method name to call
     * @param array<int|string, mixed> $args The arguments to pass to the method
     * @return $this
     */
    private function invokeMethod(string $method, array $args): self
    {
        $class = $this->instance::class;
        $cacheKey = $class . '::' . $method;

        // Check method existence with caching if enabled
        if ($this->config->enableMethodCaching) {
            if (!isset(self::$methodCache[$cacheKey])) {
                self::$methodCache[$cacheKey] = is_callable([$this->instance, $method]);
            }

            if (!self::$methodCache[$cacheKey]) {
                throw new ChainMethodNotFoundException(
                    sprintf('Method %s::%s() not found.', $class, $method)
                );
            }
        } else {
            if (!is_callable([$this->instance, $method])) {
                throw new ChainMethodNotFoundException(
                    sprintf('Method %s::%s() not found.', $class, $method)
                );
            }
        }

        try {
            foreach ($this->extensions as $extension) {
                $extension->beforeMethodCall($method, $args);
            }

            $result = $this->instance->{$method}(...$args);
            $this->setResult($result);

            foreach ($this->extensions as $extension) {
                $extension->afterMethodCall($method, $result);
            }
        } catch (\Throwable $error) {
            $this->notifyMethodError($method, $args, $error);

            throw $error;
        }

        return $this;
    }

    /**
     * Execute a side effect without altering the current instance reference.
     * @param callable(object): mixed $fn
     * @return $this
     */
    public function tap(callable $fn): self
    {
        $fn($this->instance);

        return $this;
    }

    /** Replace the current value and instance with the object returned by mapper */
    public function map(callable $fn): self
    {
        $mapped = $fn($this->currentValue());
        if (!is_object($mapped)) {
            throw new ChainInvalidOperationException('map() must return an object.');
        }
        $this->replaceCurrentObject($mapped);

        return $this;
    }

    /** Current wrapped object */
    public function instance(): object
    {
        return $this->instance;
    }

    /**
     * Return the current object after verifying its class.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function requireInstance(string $class): object
    {
        if (!$this->instance instanceof $class) {
            throw new ChainInvalidOperationException(sprintf(
                'Current instance is %s, not %s.',
                $this->instance::class,
                $class
            ));
        }

        return $this->instance;
    }

    /**
     * Get last scalar/object-returning method call result if available, otherwise the current instance.
     */
    public function get(): mixed
    {
        return $this->currentValue();
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
        $current = $this->currentValue();
        foreach ($pipes as $pipe) {
            $result = $pipe($current);
            $this->setResult($result);
            $current = $result;
        }

        return $this;
    }

    /** Clone chain and its current state for independent branching. */
    public function clone(): self
    {
        $cloned = clone $this;
        $cloned->instance = $this->config->enableDeepCloning
            ? DeepCloner::clone($this->instance)
            : clone $this->instance;

        if ($this->hasResult && is_object($this->result)) {
            $cloned->result = $this->result === $this->instance
                ? $cloned->instance
                : ($this->config->enableDeepCloning ? DeepCloner::clone($this->result) : clone $this->result);
        }

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
        $value = $this->currentValue();

        if (is_array($value) || $value instanceof \Traversable) {
            foreach ($value as $key => $item) {
                $fn($item, $key);
            }
        }

        return $this;
    }

    /** @internal */
    protected function setResult(mixed $result): void
    {
        $this->result = $result;
        $this->hasResult = true;

        if (is_object($result)) {
            $this->instance = $result;
        }
    }

    /** @internal */
    protected function replaceCurrentObject(object $object): void
    {
        $this->instance = $object;
        $this->result = $object;
        $this->hasResult = true;
    }

    /** @internal */
    protected function currentValue(): mixed
    {
        return $this->hasResult ? $this->result : $this->instance;
    }

    /**
     * @param array<int|string, mixed> $args
     */
    private function notifyMethodError(string $method, array $args, \Throwable $error): void
    {
        foreach ($this->extensions as $extension) {
            if (!$extension instanceof ChainErrorExtensionInterface) {
                continue;
            }

            try {
                $extension->onMethodError($method, $args, $error);
            } catch (\Throwable) {
                // The original method failure always takes precedence.
            }
        }
    }
}
