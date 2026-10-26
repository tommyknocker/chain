# Migration Guide

## Composite conditions

Composite conditions now execute an explicit action callback. Replace the former continuation style:

```php
// Before
Chain::of($user)
    ->whenAll(fn ($user) => $user->isActive())
    ->tap(fn ($user) => $user->notify());
```

with:

```php
// Now
Chain::of($user)->whenAll(
    [fn ($user) => $user->isActive()],
    fn ($chain) => $chain->tap(fn ($user) => $user->notify())
);
```

The explicit form prevents a false condition from accidentally allowing a later operation to run.

## PHP version and timeout

The minimum PHP version is 8.5. `timeout()` now requires `ext-pcntl` and interrupts callbacks instead of only reporting an overrun after the callback has completed.

## Configuration and resolver scope

`Chain::configure()` and `Chain::setResolver()` remain supported, but they affect only chains created later. Use `withConfig()` and `withResolver()` for a local override:

```php
$chain = Chain::of($task)
    ->withConfig(new ChainConfig(maxRetryAttempts: 5));
```

## Result values

`get()` now preserves a method result of `null`. It no longer substitutes the current object in that case.
