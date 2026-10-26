# Roadmap

## 1. Stabilize the 2.x contract

- Publish the PHP 8.5 minimum requirement and the breaking callback API for composite conditions.
- Document the state model: `instance()` is the current method receiver; `get()`/`value()` return the current value, including `null`.
- Document that `timeout()` requires PCNTL and can interrupt PHP code only in environments where PCNTL signals are delivered.
- Add a migration guide from the former `whenAll()`, `whenAny()`, and `whenNone()` signatures.
- Add contract tests for every public method and combinations of `change()`, `map()`, `pipe()`, `clone()`, and nullable results.

Exit criterion: a versioned public API reference and tests that describe observable behavior rather than implementation details.

## 2. Make runtime configuration local and explicit — completed

- `ChainConfig` is immutable.
- `Chain::configure()` and `Chain::setResolver()` remain bootstrap defaults for new chains.
- `withConfig()` and `withResolver()` override those defaults for one chain without affecting another.
- Define extension lifecycle, error handling, clone behavior, and events for callback-based operations.
- Add benchmark coverage before retaining or expanding method-call caching.

Exit criterion: two chains can use different resolvers, retry policies, and extensions without affecting one another.

## 3. Improve developer experience and type information

- Added `invoke(string $method, mixed ...$arguments)` for methods colliding with Chain's own API.
- Added generic `requireInstance(class-string<T>): T` for runtime-verified PHPStan types after dynamic transitions.
- Published an API reference, state model, and migration guide.
- Define and publish a compatibility policy before the next stable release.
- Add mutation testing or focused property/invariant tests for state transitions.

Exit criterion: common misuse is caught by static analysis or produces a precise library exception.

## 4. Harden resilience primitives

- Extend `retry()` with retry predicates, exponential backoff with jitter, attempt metadata, and explicit idempotency guidance.
- Decide whether to support nested timeouts; if so, isolate alarm state safely.
- Add an opt-in error event to extensions and preserve useful context on failures.
- Provide deterministic clock and sleeper abstractions for fast tests.

Exit criterion: retries and time limits have specified, testable behavior for side effects and failures.

## 5. Add collection and value combinators

- Introduce value-oriented operators deliberately, such as `filter`, `flatMap`, `recover`, `inspect`, and `finally`.
- Keep object-context operations distinct from value transformations so context switching remains predictable.
- Add lazy iterable support only after the eager semantics and error propagation rules are specified.

Exit criterion: each new combinator has a clear input/output contract and does not create another implicit state mode.

## 6. Explore concurrency only as a separate execution model

- Define an async abstraction first; PHP fibers alone do not make blocking callbacks asynchronous.
- Integrate with a chosen event loop or promise implementation through a separate optional package.
- Design parallel operators with explicit ordering, cancellation, bounded concurrency, and aggregation semantics.
- Keep the synchronous core dependency-free and make async APIs opt-in.

Exit criterion: concurrent workflows have a documented scheduler, cancellation model, and deterministic tests.

## 7. Add transactions only through adapters

- Do not infer transactionality from arbitrary chained method calls.
- Provide adapters for concrete resources such as PDO, ORM units of work, or message outboxes.
- Make transaction boundaries, rollback conditions, nested transaction behavior, and compensation explicit.

Exit criterion: transaction behavior is delegated to a real transactional resource and cannot falsely imply rollback of arbitrary side effects.
