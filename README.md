# Chain

[![CI](https://github.com/tommyknocker/chain/actions/workflows/ci.yml/badge.svg)](https://github.com/tommyknocker/chain/actions)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.0+-777bb3.svg)](https://www.php.net/)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)

Fluent method chaining & context switching utility for PHP. Call methods across objects, transform or branch chains, and conditionally execute logic in a concise style.

## Quick Example
```php
use tommyknocker\chain\Chain;

// Process user data through multiple transformations
$orderTotal = Chain::of(new User('Alice', 25))
    ->setEmail('alice@example.com')
    ->tap(fn($u) => logger()->info("Processing order for: " . $u->getName()))
    ->map(fn($user) => new Order(strlen($user->getName()) * 10))
    ->when(
        fn($order) => $order->getTotal() > 30,
        fn($chain) => $chain->applyDiscount(5)
    )
    ->getTotal()
    ->get();

echo $orderTotal; // 45 (50 - 5 discount)
```

## Installation
```bash
composer require tommyknocker/chain
```

## Core Concepts
- Start with `Chain::of($object)` or `Chain::of(ClassName::class, ...$args)`
- Every method call stays fluent; if a method returns an object, the chain context switches to it automatically
- Use `change($idOrObject)` to jump to another object (resolved via PSR-11 container if configured)
- Use `get()` to read the last result (or the current instance if there is no last result)

## API Overview

**Core Methods:**
- `of(string|object $target, ...$args): Chain` - Start a chain from an object or instantiate a class
- `get(): mixed` - Get final result
- `value(): mixed` - Alias for get(), more semantic
- `instance(): object` - Get current wrapped object

**Transformation:**
- `tap(callable $fn): Chain` - Execute side effects
- `map(callable $fn): Chain` - Transform to another object
- `pipe(callable ...$pipes): Chain` - Functional pipeline

**Enhanced Control Flow:**
- `whenAll(callable ...$conditions): Chain` - Execute when ALL conditions are true
- `whenAny(callable ...$conditions): Chain` - Execute when ANY condition is true  
- `whenNone(callable ...$conditions): Chain` - Execute when NO conditions are true
- `when(bool|callable $cond, callable $cb, ?callable $else = null): Chain` - Conditional execution
- `unless(bool|callable $cond, callable $cb, ?callable $else = null): Chain` - Inverse conditional
- `clone(): Chain` - Branch immutably

**Resilience:**
- `rescue(callable $callback, callable $handler): Chain` - Handle exceptions with fallback
- `catch(string $exceptionClass, callable $callback, callable $handler): Chain` - Catch specific exceptions
- `retry(int $times, callable $callback, int $delayMs = 0): Chain` - Retry with backoff
- `timeout(int $seconds, callable $callback): Chain` - Timeout protection

**Iteration & Debugging:**
- `each(callable $fn): Chain` - Iterate over collections
- `dump(string $label = ''): Chain` - Debug output, continues chain
- `dd(string $label = ''): never` - Dump and die

**Container Integration:**
- `change(string|object $target): Chain` - Switch to another object (PSR-11)

**Configuration & Extensions:**
- `Chain::configure(ChainConfig $config): void` - Configure Chain behavior
- `addExtension(ChainExtensionInterface $extension): Chain` - Add extension for monitoring/logging

## Examples

### Basic Chaining
```php
// Create instance and chain
$result = Chain::of(new User('Alice', 25))
    ->getName()
    ->map(fn($user) => new Order(strlen($user->getName()) * 10))
    ->getTotal()
    ->get();

// Or instantiate via of()
$result = Chain::of(StringBuilder::class, 'Hello')
    ->append(' World')
    ->uppercase()
    ->toString()
    ->get();
```

### Conditional Logic
```php
// Smart banking: apply bonus for high-value accounts, charge fees for low balances
$finalBalance = Chain::of(new Account())
    ->deposit(500)
    ->when(
        fn($acc) => $acc->getBalance() > 300,
        fn($chain) => $chain->addBonus()  // +100 bonus
    )
    ->unless(
        fn($acc) => $acc->getBalance() < 100,
        fn($chain) => $chain->withdraw(50)  // maintenance fee
    )
    ->getBalance()
    ->get();

echo $finalBalance; // 550 (500 + 100 bonus - 50 fee)
```

### Enhanced Conditional Logic
```php
// Multiple condition checking
$result = Chain::of(new User('Alice', 25))
    ->whenAll(
        fn($u) => $u->isAdult(),
        fn($u) => strlen($u->getName()) > 3,
        fn($u) => $u->getAge() < 50
    )
    ->tap(fn($u) => $u->setEmail('alice@example.com'))
    ->getEmail()
    ->get();

// Any condition can be true
$result = Chain::of(new User('Bob', 16))
    ->whenAny(
        fn($u) => $u->isAdult(),
        fn($u) => $u->getAge() > 15,
        fn($u) => strlen($u->getName()) > 2
    )
    ->tap(fn($u) => $u->addRole('verified'))
    ->getRoles()
    ->get();

// No conditions should be true
$result = Chain::of(new User('Charlie', 25))
    ->whenNone(
        fn($u) => $u->getAge() > 30,
        fn($u) => $u->getAge() < 18,
        fn($u) => strlen($u->getName()) < 3
    )
    ->tap(fn($u) => $u->addRole('special'))
    ->getRoles()
    ->get();
```
```php
// Complex order processing with business rules
$total = Chain::of(new Order(100.0))
    ->when(
        fn($order) => $order->getTotal() > 50,
        fn($chain) => $chain->applyDiscount(10)  // $10 off for orders > $50
    )
    ->unless(
        fn($order) => $order->getTotal() < 20,
        fn($chain) => $chain->addTax(0.08)  // 8% tax unless small order
    )
    ->getTotal()
    ->get();

echo $total; // 97.20 (100 - 10 discount + 8% tax on 90)
```

### Pipeline
```php
// Calculate price with multiple transformations
$finalPrice = Chain::of(new Calculator(100))
    ->subtract(20)  // Apply discount
    ->multiply(1.08)  // Add 8% tax
    ->pipe(
        fn($c) => $c->getValue(),
        fn($v) => round($v, 2),  // Round to 2 decimals
        fn($v) => max($v, 0)  // Ensure non-negative
    )
    ->get();

echo $finalPrice; // 86.4
```

### Pipelines with pipe()
```php
// Text processing pipeline for user input sanitization
$sanitized = Chain::of(new StringBuilder('  Hello@World123  '))
    ->pipe(
        fn($b) => $b->toString(),
        fn($text) => trim($text),
        fn($text) => strtolower($text),
        fn($text) => preg_replace('/[^a-z0-9]/', '', $text)
    )
    ->get();

echo $sanitized; // 'helloworld123'
```

### Tap for Side Effects
```php
// Using tap for logging without breaking the chain
$logger = Chain::of(new Logger())
    ->log('Starting process')
    ->log('Loading data')
    ->tap(fn($l) => print("Current logs: " . $l->count() . "\n"))
    ->log('Processing')
    ->tap(fn($l) => print("Logs so far: " . implode(', ', $l->getLogs()) . "\n"))
    ->log('Completed')
    ->instance();

echo "Total logs: " . $logger->count() . "\n";
```

### Branching
```php
// Calculate different pricing scenarios from same base
$baseCalc = Chain::of(new Calculator(100));

$retailPrice = $baseCalc->clone()->multiply(1.5)->getValue()->get();  // 150
$wholesalePrice = $baseCalc->clone()->multiply(1.2)->getValue()->get();  // 120
$memberPrice = $baseCalc->clone()->multiply(0.9)->getValue()->get();  // 90
```

### Immutable Branching with clone()
```php
// Explore different account scenarios without mutating original
$baseAccount = Chain::of(new Account());

$scenario1 = $baseAccount->clone()
    ->deposit(1000)
    ->withdraw(200)
    ->getBalance()->get();  // 800

$scenario2 = $baseAccount->clone()
    ->deposit(500)
    ->addBonus()
    ->getBalance()->get();  // 600 (500 + 100 bonus)

$original = $baseAccount->getBalance()->get();  // 0 (unchanged)
```

### Composite Scenario
```php
// Complex data processing workflow with multiple stages
$report = Chain::of(new DataProcessor())
    ->addItem(100)
    ->addItem(250)
    ->addItem(75)
    ->addItem(300)
    ->tap(fn($p) => logger()->info("Processing " . $p->count() . " items"))
    ->filter(fn($x) => $x >= 100)  // Only items >= 100
    ->transform(fn($x) => $x * 1.08)  // Add 8% markup
    ->pipe(
        fn($p) => ['total' => $p->sum(), 'count' => $p->count()],
        fn($stats) => new Report($stats['total'], $stats['count']),
        fn($report) => $report->format()
    )
    ->get();

echo $report; // "Total: 702.00, Count: 3, Average: 234.00"
```

### Timeout Protection
```php
// Protect against slow operations
try {
    $result = Chain::of(new Calculator(10))
        ->timeout(2, function ($calc) {
            // Simulate slow operation
            usleep(1000000); // 1 second
            return $calc->add(5);
        })
        ->getValue()
        ->get();
    
    echo "Result: $result\n";
} catch (\tommyknocker\chain\Exception\ChainTimeoutException $e) {
    echo "Operation timed out: " . $e->getMessage() . "\n";
}
```

### Configuration & Extensions
```php
// Configure Chain behavior
Chain::configure(ChainConfig::performance());

// Add monitoring extension
class LoggingExtension implements ChainExtensionInterface
{
    private array $logs = [];

    public function beforeMethodCall(string $method, array $args): void
    {
        $this->logs[] = "Before: {$method}";
    }

    public function afterMethodCall(string $method, mixed $result): void
    {
        $this->logs[] = "After: {$method}";
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}

$logger = new LoggingExtension();

$result = Chain::of(new Calculator(10))
    ->addExtension($logger)
    ->add(5)
    ->multiply(2)
    ->getValue()
    ->get();

// Check logs
foreach ($logger->getLogs() as $log) {
    echo "$log\n";
}
```
```php
// PSR-11 Container integration with change()
$container = new SimpleContainer();
$container->set('email', new EmailService());
$container->set('notification', new NotificationService());

Chain::setResolver($container);

// Switch between services dynamically
$result1 = Chain::of($container->get('email'))
    ->send('user@example.com')
    ->get();

$result2 = Chain::of($container->get('email'))
    ->change('notification')  // Switch to notification service
    ->notify('Important update')
    ->get();

echo "$result1\n";  // "Email sent to user@example.com"
echo "$result2\n";  // "Notification: Important update"
```

## Development

### Installation
```bash
composer install
```

### Testing
```bash
composer test              # Run all tests
composer test:coverage     # Run tests with coverage report
composer test:ci          # Run tests for CI (with JUnit output)
```

### Code Quality
```bash
composer phpstan          # Static analysis
composer phpstan:baseline # Generate PHPStan baseline
composer cs:fix           # Fix code style issues
composer cs:check         # Check code style (dry-run)
composer quality          # Run all quality checks
composer quality:fix      # Run quality checks and fix issues
```

### Examples
```bash
composer examples         # Test all examples
```

### Release Management
```bash
composer release          # Create new release
```

## Examples

See the [`examples/`](examples/) directory for working examples:
- [`workflow.php`](examples/workflow.php) - **Complete workflow** with User→Profile context switching
- [`conditionals.php`](examples/conditionals.php) - Conditional execution with when/unless
- [`branching.php`](examples/branching.php) - Clone chains for independent branches
- [`pipeline.php`](examples/pipeline.php) - Functional pipelines with pipe()
- [`processing.php`](examples/processing.php) - Data processing with each(), dump(), value()
- [`resilience.php`](examples/resilience.php) - Error handling with rescue(), catch(), retry()
- [`container.php`](examples/container.php) - PSR-11 container integration
- [`advanced-features.php`](examples/advanced-features.php) - **NEW!** All enhanced features demo

Run examples:
```bash
php examples/advanced-features.php  # Run specific example
composer examples                   # Test all examples
```

## License
MIT. See LICENSE file.

## Changelog
See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.

