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
./vendor/bin/phpunit tests/ChainTest.php
./vendor/bin/phpunit tests/ChainNewMethodsTest.php
```

## Test Files

### [ChainTest.php](ChainTest.php)
Core functionality tests for the Chain class.

**Tests:**
- `testBasic()` - Basic method chaining and result retrieval
- `testSwitchBetweenObjectsUsingContainer()` - Container integration with `change()`
- `testChange()` - Switching between objects
- `testTap()` - Side effects with `tap()`
- `testMap()` - Object transformation with `map()`
- `testWhen()` - Conditional execution with `when()`
- `testUnless()` - Inverse conditional with `unless()`
- `testPipe()` - Functional pipelines
- `testClone()` - Immutable branching

**Fixtures used:** `DummyClass`, anonymous classes

### [ChainNewMethodsTest.php](ChainNewMethodsTest.php)
Extended tests for all Chain methods with detailed scenarios.

**Tests:**
- **Chain Creation:**
  - `testOfCreatesChainInstance()` - `Chain::of()` creates proper instance
  - `testMakeCreatesInstanceAndChain()` - `Chain::of(ClassName::class)` instantiates class
  
- **Result Handling:**
  - `testGetReturnsLastResult()` - `get()` returns last method result
  - `testGetReturnsInstanceWhenNoResult()` - `get()` returns instance when no result
  - `testGetAfterTapReturnsInstance()` - `tap()` doesn't affect result
  
- **Method Chaining:**
  - `testCallForwardsToMagicCall()` - Magic `__call()` forwards method calls
  - `testChainingSwitchesContext()` - Context switches on object return
  
- **Transformations:**
  - `testMapTransformsInstance()` - `map()` transforms to new object
  - `testMapThrowsOnNonObject()` - `map()` validates object return
  
- **Side Effects:**
  - `testTapExecutesSideEffect()` - `tap()` executes callback
  - `testTapDoesNotChangeInstance()` - `tap()` preserves instance
  
- **Conditionals:**
  - `testWhenExecutesCallbackOnTrue()` - `when()` with truthy condition
  - `testWhenSkipsCallbackOnFalse()` - `when()` with falsy condition
  - `testWhenWithCallableCondition()` - `when()` with callable
  - `testWhenWithElseCallback()` - `when()` with else branch
  - `testUnlessExecutesCallbackOnFalse()` - `unless()` inverse logic
  - `testUnlessSkipsCallbackOnTrue()` - `unless()` skips on true
  
- **Pipelines:**
  - `testPipeExecutesSequence()` - `pipe()` chains transformations
  - `testPipeWithMixedTypes()` - `pipe()` handles scalars and objects
  
- **Branching:**
  - `testCloneCreatesSeparateChain()` - `clone()` creates independent copy
  - `testCloneDoesNotMutateOriginal()` - `clone()` preserves original
  
- **Container Integration:**
  - `testChangeWithObject()` - `change()` with object instance
  - `testChangeWithContainerId()` - `change()` with container lookup
  - `testChangeThrowsWhenResolverNotSet()` - Validation without resolver

**Fixtures used:** `Calculator`, `DummyClass`, `User`

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
- [`Report`](fixtures/Report.php) - Report generation
- [`SimpleContainer`](fixtures/SimpleContainer.php) - PSR-11 container
- [`StringBuilder`](fixtures/StringBuilder.php) - String building
- [`User`](fixtures/User.php) - User entity

## Test Coverage

Current test metrics:
- **35 tests** in total
- **52 assertions**
- All core Chain functionality covered
- Edge cases and error conditions tested

## Writing New Tests

1. Add tests to existing test files or create new test file in `tests/`
2. Use existing fixtures from `fixtures/` or create new ones
3. Follow PHPUnit naming conventions: `test*` for test methods
4. Group related tests with comments (e.g., `// ==================== Feature Tests ====================`)
5. Run tests to verify: `composer test`

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- See [CI workflow](../.github/workflows/ci.yml) for details

