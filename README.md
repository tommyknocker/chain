# Chain

[![CI](https://github.com/tommyknocker/chain/actions/workflows/ci.yml/badge.svg)](https://github.com/tommyknocker/chain/actions)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.0-777bb3.svg)](https://www.php.net/)
[![Static Analysis](https://img.shields.io/badge/static%20analysis-phpstan-brightgreen.svg)](https://phpstan.org/)

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
- `of(string|object $target, ...$args): Chain` - Start a chain from an object or instantiate a class
- `change(string|object $target): Chain` - Switch to another object
- `tap(callable $fn): Chain` - Execute side effects
- `map(callable $fn): Chain` - Transform to another object
- `when(bool|callable $cond, callable $cb, ?callable $else = null): Chain` - Conditional execution
- `unless(bool|callable $cond, callable $cb, ?callable $else = null): Chain` - Inverse conditional
- `pipe(callable ...$pipes): Chain` - Functional pipeline
- `clone(): Chain` - Branch immutably
- `get(): mixed` - Get final result
- `instance(): object` - Get current wrapped object

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

### Conditional Flow (when / unless)
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

### Container Integration
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

### Tests
```bash
composer test
```

### Static Analysis
```bash
composer phpstan
```

### Code Style
```bash
composer cs:fix   # fix
composer cs:check # dry-run
```

## Examples

See the [`examples/`](examples/) directory for more working examples:
- [`basic.php`](examples/basic.php) - Basic method chaining
- [`conditional.php`](examples/conditional.php) - Conditional execution with when/unless
- [`pipeline.php`](examples/pipeline.php) - Functional pipelines
- [`branching.php`](examples/branching.php) - Clone chains for independent branches
- [`tap.php`](examples/tap.php) - Side effects with tap
- [`container.php`](examples/container.php) - PSR-11 container integration
- [`complex-workflow.php`](examples/complex-workflow.php) - Complex data processing workflow

Run examples:
```bash
php examples/basic.php
bash scripts/test-examples.sh  # Test all examples
```

## License
MIT. See LICENSE file.

