# Chain

[![CI](https://github.com/tommyknocker/chain/actions/workflows/ci.yml/badge.svg)](https://github.com/tommyknocker/chain/actions)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.5+-777bb3.svg)](https://www.php.net/)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)

Three focused PHP utilities in one package: fluent object calls, reusable step
plans, and bounded concurrent HTTP requests.

## Installation

```bash
composer require tommyknocker/chain
```

The package requires PHP 8.5+, `ext-curl`, and `psr/container`. `ext-curl` is
required even if you only use `Chain` or `Flow`, because all three utilities are
part of one package. `Chain::timeout()` additionally requires `ext-pcntl`.

## Choose an execution model

| Utility | Use it for | When work runs | Result |
| --- | --- | --- | --- |
| `Chain` | Fluent calls and transformations around an object | Immediately as methods are called | A current object and current value |
| `Flow` | A reusable sequence of application steps | On `run()` or `execute()` | A final value or detailed execution |
| `Batch` | Fetching or downloading independent HTTP resources | Inside a blocking `run()` | A report of successes and failures |

These utilities are independent and can be combined where useful.

## Chain: work with an object

Wrap an object, call its methods fluently, and read the current value:

```php
use tommyknocker\chain\Chain;

$counter = new class {
    private int $value = 0;

    public function next(): int { return ++$this->value; }
    public function reset(): void { $this->value = 0; }
};

$chain = Chain::of($counter);
$value = $chain->next()
    ->pipe(static fn (int $n): int => $n * 10)
    ->get();                         // 10

$receiver = $chain->instance();      // $counter
$result = $chain->reset()->get();    // null
```

`get()` returns the current value. Initially it is the wrapped object; after a
wrapped method call it is that method's result, including `null` from a `void`
method. `instance()` returns the current object used as the receiver for
wrapped method calls. An object returned by a call becomes the new receiver;
a scalar or `null` leaves the receiver unchanged.

Use `invoke('method', ...$args)` when a wrapped method name conflicts with
the Chain API. `requireInstance(Foo::class)` verifies the receiver type for
PHPStan after a dynamic transition. Chain also provides branching, conditions,
transformations, retry policies, and extension hooks.

See the [state model](docs/state-model.md), [Chain examples](docs/chain-examples.md),
and [API reference](docs/api-reference.md).

## Flow: compose reusable steps

Build a plan once, then run it with different inputs:

```php
use tommyknocker\chain\Flow;

$base = Flow::of(static fn (string $name): string => trim($name));
$normalized = $base->then(static fn (string $name): string => strtolower($name));

$base->run('  PHP  ');        // 'PHP'
$normalized->run('  PHP  ');  // 'php'
```

Steps can be callables, `StepInterface` objects, class names resolved through a
PSR-11 container, or nested flows. `then()` returns a new plan. `Flow::stop()`
ends execution successfully, while `execute()` provides a result, failure, stop
state, and step trace.

Plan immutability is structural: step objects, closure captures, and payloads
are not cloned and may retain mutable state between runs. See the [Flow guide](docs/flow.md)
and the runnable [Flow example](examples/flow.php).

## Batch: fetch and download URLs

Fetch response bodies into memory:

```php
use tommyknocker\chain\Batch;

$texts = Batch::fetch([
    'article-1' => 'https://example.com/first.txt',
    'article-2' => 'https://example.com/second.txt',
])->concurrency(8)->run()->throwIfFailed()->bodies();

// $texts is keyed by article ID.
```

Or stream responses to files, with file names supplied as input keys:

```php
$report = Batch::download([
    'avatar-1.jpg' => 'https://example.com/images/first.jpg',
    'avatar-2.jpg' => 'https://example.com/images/second.jpg',
])->toDirectory('/var/app/images')
    ->fileNamesFromKeys()
    ->concurrency(8)
    ->run();

$downloads = $report->downloads(); // Successful downloads, keyed by file name.
$failures = $report->failures();   // Final failures, keyed by file name.
```

`Batch` uses cURL multi for bounded concurrent HTTP I/O. `run()` blocks until
the batch finishes or stops; it does not run PHP callbacks in parallel. The
report can contain partial results. `throwIfFailed()` throws when failures were
recorded; it does not roll back successful requests or files, and cancellation
does not create failures for work that never started.

For request headers, authentication, retries, size limits, file naming, and
other controls, see the [Batch guide](docs/batch.md) and [API reference](docs/api-reference.md#batch-http-utility).

## Documentation

- [API reference](docs/api-reference.md) and [compatibility policy](docs/compatibility.md)
- [Chain state model](docs/state-model.md) and [Chain examples](docs/chain-examples.md)
- [Flow guide](docs/flow.md)
- [Batch HTTP guide](docs/batch.md)
- [Runnable examples](examples/README.md)
- [Design note: Chain, Flow, and Batch execution models](https://krivoplyas.42x.ru/notes/chain-execution-models/)
- [Changelog](CHANGELOG.md)

## Development

```bash
composer install
composer quality       # Style, PHPStan, and tests
composer examples      # Run the examples
composer test          # PHPUnit only
composer test:coverage # Coverage report
composer phpstan       # Static analysis
composer cs:fix        # Apply code style fixes
```

## License

MIT. See [LICENSE](LICENSE).
