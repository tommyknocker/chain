# Tests

This directory contains the test suite for the Chain library.

## Running Tests

Run all tests:
```bash
composer test
# or
./vendor/bin/phpunit
```

Run with coverage:
```bash
./vendor/bin/phpunit --coverage-html coverage
```

Run specific test file:
```bash
./vendor/bin/phpunit tests/ChainCoreTest.php
./vendor/bin/phpunit tests/ChainAdvancedTest.php
./vendor/bin/phpunit tests/ChainResilienceTest.php
```

## Test Structure

### [ChainCoreTest.php](ChainCoreTest.php)
Core functionality tests for basic Chain operations.

**Tests:**
- `testBasic()` - Basic method chaining
- `testSwitchBetweenObjectsUsingContainer()` - Container integration with `change()`
- `testTapExecutesCallbackWithoutChangingInstance()` - Side effects with `tap()`
- `testMapReplacesInstance()` - Object transformation with `map()`
- `testMapThrowsIfNotObject()` - Validation
- `testMakeCreatesNewInstance()` - Instantiation via `of()`
- `testMakeAndChainMethods()` - Chaining after instantiation

**Features tested:** of(), __call(), tap(), map(), change(), get(), instance()

### [ChainAdvancedTest.php](ChainAdvancedTest.php)
Advanced features: conditionals, pipelines, branching.

**Test Groups:**
- **Chain::of()** - Instance creation
- **get()/value()** - Result retrieval
- **when()** - Conditional execution with callable and boolean conditions
- **unless()** - Inverse conditional execution
- **pipe()** - Functional pipelines with single and multiple functions
- **clone()** - Immutable branching for independent modifications

**Features tested:** when(), unless(), pipe(), clone(), value()

**Stats:** 25+ tests covering all advanced control flow

### [ChainResilienceTest.php](ChainResilienceTest.php)
Resilience features: error handling, retry logic, iteration, debugging.

**Test Groups:**
- **value()** - Semantic getter alias
- **dump()** - Debug output with labels
- **dd()** - Dump and die (documented behavior)
- **each()** - Iteration over arrays and traversables
- **rescue()** - General exception handling with fallback
- **catch()** - Specific exception type handling
- **retry()** - Automatic retry with backoff

**Features tested:** value(), dump(), dd(), each(), rescue(), catch(), retry()

**Stats:** 25+ tests covering resilience patterns

## Test Coverage

Current test metrics:
- **60 tests** in total
- **88 assertions**
- **All** Chain features covered
- Edge cases and error conditions tested

## Fixtures

Test fixtures are located in [`fixtures/`](fixtures/) directory. See [`fixtures/README.md`](fixtures/README.md) for details.

Available fixtures:
- [`Account`](fixtures/Account.php) - Banking operations
- [`Calculator`](fixtures/Calculator.php) - Mathematical operations
- [`Counter`](fixtures/Counter.php) - Simple counter
- [`DataProcessor`](fixtures/DataProcessor.php) - Array processing
- [`DummyClass`](fixtures/DummyClass.php) - Simple test fixture
- [`EmailService`](fixtures/EmailService.php) - Email operations
- [`Logger`](fixtures/Logger.php) - Logging functionality
- [`NotificationService`](fixtures/NotificationService.php) - Notifications
- [`Order`](fixtures/Order.php) - Order management
- [`Profile`](fixtures/Profile.php) - User profile
- [`Report`](fixtures/Report.php) - Report generation
- [`SimpleContainer`](fixtures/SimpleContainer.php) - PSR-11 container
- [`StringBuilder`](fixtures/StringBuilder.php) - String building
- [`User`](fixtures/User.php) - User entity

## Writing New Tests

1. Add tests to the appropriate test file based on feature category:
   - **ChainCoreTest.php** - for basic operations
   - **ChainAdvancedTest.php** - for control flow
   - **ChainResilienceTest.php** - for error handling/resilience
2. Use existing fixtures from `fixtures/` or create new ones if needed
3. Follow PHPUnit naming conventions: `test*` for test methods
4. Group related tests with comments (e.g., `// ==================== Feature Tests ====================`)
5. Run tests to verify: `composer test`

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- See [CI workflow](../.github/workflows/ci.yml) (if configured)
