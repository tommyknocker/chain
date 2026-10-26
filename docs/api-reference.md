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
| `inspect(callable $callback)` | Calls the callback with the current value and does not change state. |
| `filter(callable $predicate)` | Filters the current iterable value into an array and preserves keys. |
| `flatMap(callable $mapper)` | Maps every current iterable item to an iterable and flattens it into an array. |
| `recover(callable $callback, callable $handler)` | Runs a value callback; the handler result replaces an error. |
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
| `retryWith(RetryPolicy $policy, callable $callback)` | Retries with a predicate, capped exponential backoff, optional jitter, and `RetryAttempt` metadata. |
| `timeout(?int $seconds, callable $callback)` | Interrupts a callback with `ChainTimeoutException` through PCNTL. Requires `ext-pcntl`. |
| `dump(string $label = '')` | Prints the current value and continues. |
| `dd(string $label = '')` | Prints the current value and terminates the process. |

`retry()` may repeat side effects. Pass an idempotent callback or provide its own deduplication.

`retryWith()` calls its callback with the current object and a `RetryAttempt`. Use `withSleeper()` to substitute the delay mechanism for tests.

`timeout()` deliberately rejects nested calls. PCNTL exposes one process-wide alarm, so nesting cannot preserve both deadlines safely.

## Configuration, resolver, and extensions

| Method | Contract |
| --- | --- |
| `Chain::configure(ChainConfig $config)` | Sets an immutable default captured by chains created later. |
| `withConfig(ChainConfig $config)` | Replaces configuration for this chain only. |
| `Chain::setResolver(?ContainerInterface $resolver)` | Sets the default resolver for chains created later. Pass `null` to clear it. |
| `withResolver(?ContainerInterface $resolver)` | Replaces the resolver for this chain only. |
| `addExtension(ChainExtensionInterface $extension)` | Adds before/after hooks for wrapped-object calls, including `invoke()`. Implement `ChainErrorExtensionInterface` as well to observe failures. |

`ChainConfig` controls method-call caching, clone depth, retry defaults, retry delay, and timeout defaults. It is immutable.

Extensions receive `beforeMethodCall()` in registration order, then the wrapped call, then `afterMethodCall()` in registration order. If any of these steps throws, every error extension receives `onMethodError()`; errors from that observer are suppressed so the original error propagates. Cloned chains retain the same extension instances. Callbacks passed to `retry()` or `timeout()` are not extension events unless they invoke a wrapped method through the chain.

## Exceptions

| Exception | Trigger |
| --- | --- |
| `ChainException` | A target cannot be resolved. |
| `ChainMethodNotFoundException` | A wrapped method is not callable. |
| `ChainInvalidOperationException` | An invalid chain operation occurs, such as `map()` returning a scalar. |
| `ChainTimeoutException` | `timeout()` reaches its deadline. |

## Batch HTTP utility

`Batch` is separate from the mutable `Chain` state model. It runs independent
HTTP(S) URLs through `ext-curl` with bounded concurrency and returns a `Report`.

| Entry point | Contract |
| --- | --- |
| `Batch::fetch(iterable $urls)` | Buffers successful 2xx response bodies in memory. Input keys are retained. |
| `Batch::download(iterable $urls)` | Streams successful 2xx responses to files. Call `toDirectory()` before `run()`. |
| `concurrency(int $limit)` | Caps active cURL transfers; default is 8. |
| `timeout(float $seconds)` | Sets a per-transfer total timeout; default is 30 seconds. |
| `connectTimeout(float $seconds)` | Sets a per-transfer connection timeout; default is 10 seconds. |
| `acceptStatuses(int ...$statuses)` | Replaces the default accepted 2xx statuses. |
| `FetchBatch::maxBodySize(int $bytes)` | Sets the per-response memory limit; default is 16 MiB. |
| `DownloadBatch::toDirectory(string $directory)` | Sets and creates the destination directory. |
| `DownloadBatch::fileNamesFromKeys()` | Uses `id => url` keys as file names. |
| `DownloadBatch::fileNameUsing(callable $resolver)` | Builds a file name from the input key and URL. |
| `DownloadBatch::overwrite()` | Explicitly permits replacement of an existing destination file. |
| `DownloadBatch::maxDownloadSize(int $bytes)` | Caps one streamed file and deletes its temporary file if exceeded. |
| `HttpRequest` | Immutable per-job HTTP method, headers, query, body/JSON, auth, timeouts, and response expectations. |
| `HttpRetryPolicy` | Opt-in, non-blocking retry policy for temporary GET/HEAD failures. |
| `onEvent()` / `onProgress()` / `onCompleted()` / `onFailed()` | Observes immutable batch events; observer errors are suppressed. |
| `maxJobs()` / `failFast()` / `cancel()` | Bounds accepted input, stops on a final failure, or explicitly cancels remaining work. |
| `Report::bodies()` | Returns successful buffered bodies keyed like the input. |
| `Report::downloads()` / `failures()` | Returns keyed successful downloads or keyed per-job failures. |
| `Report::throwIfFailed()` | Throws `BatchFailedException`, retaining its report, when any job failed. |
