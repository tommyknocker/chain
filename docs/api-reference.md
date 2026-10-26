# API Reference

`Chain` wraps an object used as the current method receiver. Dynamic calls such as `->send()` remain runtime-dispatched through PHP's `__call()`, and any such call can replace the receiver with a different object type.

PHPStan therefore cannot soundly preserve a generic class type across a mutable chain. Use `requireInstance(Foo::class)` after a dynamic transition: it verifies the runtime class and has a generic PHPStan return type of `Foo`.

## Construction and invocation

| Method | Contract |
| --- | --- |
| `Chain::of(object|class-string, mixed ...$args)` | Wraps an existing object or creates an object from a class name. Captures current global config and resolver defaults. |
| `__call(string $method, array $args)` | Calls a callable public or magic method on the current object. |
| `invoke(string $method, mixed ...$args)` | Performs the same call explicitly. Use it when the wrapped method name conflicts with a Chain method. |
| `instance()` | Returns the current object receiver. |
| `requireInstance(class-string $class)` | Verifies and returns the current receiver with an exact generic type for PHPStan. |
| `get()` / `value()` | Returns the current value. This may be a scalar, object, collection, or `null`. |

Examples:

```php
$value = Chain::of($service)->invoke('get')->get();
$context = Chain::of($user)->requireInstance(User::class); // inferred as User
```

## State and transformations

| Method | Contract |
| --- | --- |
| `change(string|object $target)` | Changes both current object and current value. String targets resolve through the configured PSR-11 resolver. |
| `tap(callable $callback)` | Calls the callback with the current object and leaves state unchanged. |
| `map(callable $callback)` | Passes the current value to the callback. The callback must return an object; it becomes object and value. |
| `pipe(callable ...$pipes)` | Passes the current value through every pipe. If a pipe returns an object, it also becomes the method receiver. |
| `each(callable $callback)` | Calls the callback for every item when the current value is array or `Traversable`. |
| `clone()` | Creates a branch. The clone mode comes from `ChainConfig`. |

See [State model](state-model.md) for transition rules and nullable values.

## Control flow

| Method | Contract |
| --- | --- |
| `when(bool|callable $condition, callable $then, ?callable $else)` | Executes one callback with the chain. Callable conditions receive the current object. |
| `unless(...)` | Inverse of `when()`. |
| `whenAll(array $conditions, callable $then, ?callable $else)` | Executes `$then` only when every condition is true. |
| `whenAny(array $conditions, callable $then, ?callable $else)` | Executes `$then` when at least one condition is true. |
| `whenNone(array $conditions, callable $then, ?callable $else)` | Executes `$then` when every condition is false. |

Condition callbacks receive the current object. Action callbacks receive the `Chain` instance.

## Resilience and diagnostics

| Method | Contract |
| --- | --- |
| `rescue(callable $callback, callable $handler)` | Runs the callback with the current object; the handler receives any thrown `Throwable`. Both results become the current value. |
| `catch(string $exceptionClass, callable $callback, callable $handler)` | `rescue()` limited to one exception type. Other exceptions propagate. |
| `retry(?int $times, callable $callback, ?int $delayMs)` | Retries a callback after failures. `null` uses this chain's configuration. |
| `timeout(?int $seconds, callable $callback)` | Interrupts a callback with `ChainTimeoutException` through PCNTL. Requires `ext-pcntl`. |
| `dump(string $label = '')` | Prints the current value and continues. |
| `dd(string $label = '')` | Prints the current value and terminates the process. |

`retry()` may repeat side effects. Pass an idempotent callback or provide its own deduplication.

## Configuration, resolver, and extensions

| Method | Contract |
| --- | --- |
| `Chain::configure(ChainConfig $config)` | Sets an immutable default captured by chains created later. |
| `withConfig(ChainConfig $config)` | Replaces configuration for this chain only. |
| `Chain::setResolver(?ContainerInterface $resolver)` | Sets the default resolver for chains created later. Pass `null` to clear it. |
| `withResolver(?ContainerInterface $resolver)` | Replaces the resolver for this chain only. |
| `addExtension(ChainExtensionInterface $extension)` | Adds before/after hooks for wrapped-object calls, including `invoke()`. |

`ChainConfig` controls method-call caching, clone depth, retry defaults, retry delay, and timeout defaults. It is immutable.

## Exceptions

| Exception | Trigger |
| --- | --- |
| `ChainException` | A target cannot be resolved. |
| `ChainMethodNotFoundException` | A wrapped method is not callable. |
| `ChainInvalidOperationException` | An invalid chain operation occurs, such as `map()` returning a scalar. |
| `ChainTimeoutException` | `timeout()` reaches its deadline. |
