# Flow

`Flow` is an immutable, reusable execution plan for application steps. Unlike
`Chain`, it does not dispatch methods on a mutable wrapped object; unlike
`Batch`, it does not schedule independent HTTP work.

## Simple flow

```php
use tommyknocker\chain\Flow;

$result = Flow::of(
    NormalizeOrder::class,
    ValidateOrder::class,
    CalculateTotal::class,
)->run($order);
```

A step may be a `StepInterface`, class-string, callable, or nested `Flow`.
Callables receive the current payload and a `FlowContext`.

```php
$flow = Flow::of(
    static fn (string $value): string => trim($value),
    static fn (string $value): string => strtolower($value),
);
```

`then()` returns a new plan, so base flows are safe to reuse. A nested flow is a
grouping boundary, not a separate job: a failure or `Flow::stop()` propagates to
the outer flow.

## Named steps and dependency resolution

```php
use tommyknocker\chain\FlowContext;
use tommyknocker\chain\StepInterface;

final class ValidateOrder implements StepInterface
{
    public function handle(mixed $payload, FlowContext $context): mixed
    {
        // validate and return the next payload
        return $payload;
    }
}

$result = Flow::of(ValidateOrder::class)
    ->withResolver($container)
    ->withContext(['traceId' => 'checkout-42'])
    ->run($order);
```

Without a resolver, class-string steps must be instantiable without arguments.

## Controlled stop and detailed execution

```php
$execution = Flow::of(
    ValidateOrder::class,
    static fn (Order $order) => $order->isAlreadyPaid()
        ? Flow::stop($order)
        : $order,
    ChargePayment::class,
)->execute($order);

if ($execution->failed()) {
    throw $execution->failure();
}

$result = $execution->result();
```

`run()` is shorthand for `execute(...)->unwrap()` and throws
`FlowFailedException` with the failed step index, name, and original exception.

## Events

```php
$flow = Flow::of(ValidateOrder::class)
    ->onEvent(static function (FlowEvent $event): void {
        // started, completed, failed, or stopped
    });
```

Observer failures are suppressed: instrumentation cannot change the outcome of
the flow.
