# Examples

This directory contains practical examples demonstrating various features of the Chain library.

## Running Examples

Run all examples test script:
```bash
bash scripts/test-examples.sh
```

Run a specific example:
```bash
php examples/basic.php
```

## Examples Overview

### [quick-example.php](quick-example.php)
The main quick start example from README - demonstrates complete user order processing workflow.
- Uses: `User`, `Order` fixtures
- Features: `tap()`, `map()`, `when()`, method chaining

### [basic.php](basic.php)
Demonstrates basic method chaining with `map()` and `get()`.
- Uses: `User`, `Order` fixtures
- Features: Direct method calls via `__call()`, `map()` transformation

### [conditional.php](conditional.php)
Shows conditional execution with `when()` and `unless()`.
- Uses: `Account` fixture
- Features: Conditional logic for business rules

### [conditional-logic.php](conditional-logic.php)
Advanced conditional logic example with banking operations.
- Uses: `Account` fixture
- Features: Smart banking with bonuses and fees based on balance

### [branching.php](branching.php)
Shows how to clone chains for independent execution branches.
- Uses: `Counter` fixture
- Features: `clone()` for immutable branching

### [branching-scenarios.php](branching-scenarios.php)
Multiple pricing scenarios from the same base calculator.
- Uses: `Calculator` fixture
- Features: `clone()` for retail/wholesale/member pricing

### [immutable-branching.php](immutable-branching.php)
Demonstrates immutable branching with account scenarios without mutating the original.
- Uses: `Account` fixture
- Features: `clone()` to explore different scenarios independently

### [pipeline.php](pipeline.php)
Demonstrates functional pipelines using `pipe()` for data transformation.
- Uses: `Calculator` fixture
- Features: `pipe()` for sequential transformations

### [tap.php](tap.php)
Demonstrates `tap()` for side effects without breaking the chain.
- Uses: `Logger` fixture
- Features: `tap()` for logging and debugging

### [container.php](container.php)
Shows integration with PSR-11 Container and the `change()` method for switching between services.
- Uses: `SimpleContainer`, `EmailService`, `NotificationService` fixtures
- Features: `change()` for dynamic service switching

### [make.php](make.php)
Demonstrates `Chain::of()` for instantiating classes and starting chains.
- Uses: `StringBuilder` fixture
- Features: `Chain::of(ClassName::class, ...$args)` instantiation

### [complex-workflow.php](complex-workflow.php)
A comprehensive example showing multiple chaining features together: filtering, transforming, tapping, and piping.
- Uses: `DataProcessor`, `Report` fixtures
- Features: Complex data processing with `filter()`, `transform()`, `tap()`, `pipe()`

## Fixtures

All examples use fixtures from `tests/fixtures/`. This ensures:
- **Consistency**: Same classes used in tests and examples
- **Maintainability**: Update fixture once, affects both tests and examples
- **Clarity**: No duplicate class definitions

Available fixtures:
- [`Account`](../tests/fixtures/Account.php) - Banking operations (deposit, withdraw, balance)
- [`Calculator`](../tests/fixtures/Calculator.php) - Mathematical operations (add, subtract, multiply, divide)
- [`Counter`](../tests/fixtures/Counter.php) - Simple counter (increment, decrement)
- [`DataProcessor`](../tests/fixtures/DataProcessor.php) - Array processing (filter, transform, sum)
- [`EmailService`](../tests/fixtures/EmailService.php) - Email operations
- [`Logger`](../tests/fixtures/Logger.php) - Logging functionality
- [`NotificationService`](../tests/fixtures/NotificationService.php) - Notification operations
- [`Order`](../tests/fixtures/Order.php) - Order management
- [`Report`](../tests/fixtures/Report.php) - Report generation
- [`SimpleContainer`](../tests/fixtures/SimpleContainer.php) - PSR-11 container implementation
- [`StringBuilder`](../tests/fixtures/StringBuilder.php) - String building operations
- [`User`](../tests/fixtures/User.php) - User entity with properties
- [`DummyClass`](../tests/fixtures/DummyClass.php) - Simple test fixture

## Adding New Examples

1. Create a new PHP file in `examples/`
2. Use existing fixtures from `tests/fixtures/` or add new ones if needed
3. Add documentation to this README with a link: `### [filename.php](filename.php)`
4. Run `bash scripts/test-examples.sh` to verify it works



