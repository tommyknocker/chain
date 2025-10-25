# Examples

This directory contains practical examples demonstrating Chain library features.

## Running Examples

Run all examples:
```bash
bash scripts/test-examples.sh
```

Run a specific example:
```bash
php examples/workflow.php
```

## Examples Overview

### [workflow.php](workflow.php)
Complete real-world user registration workflow with profile creation.
- **Features**: of(), tap(), map(), when(), unless(), pipe(), clone()
- **Use Case**: User registration → validation → profile setup → conditional logic
- **Demonstrates**: Context switching from User to Profile

### [conditionals.php](conditionals.php)
Conditional execution with business rules.
- **Features**: when(), unless()
- **Use Case**: Banking operations with bonuses and fees based on balance

### [branching.php](branching.php)
Immutable branching for exploring different scenarios.
- **Features**: clone()
- **Use Case**: Price calculations (retail/wholesale/member pricing)

### [pipeline.php](pipeline.php)
Functional pipelines for data transformation.
- **Features**: pipe()
- **Use Case**: Sequential transformations with price calculations

### [processing.php](processing.php)
Data processing with iteration and debugging.
- **Features**: tap(), pipe(), each(), dump(), value()
- **Use Case**: Data filtering, transformation, and reporting

### [resilience.php](resilience.php)
Error handling and resilience patterns.
- **Features**: rescue(), catch(), retry()
- **Use Case**: API calls with automatic retries and fallback handling

### [advanced-features.php](advanced-features.php)
**NEW!** Comprehensive demo of all enhanced features.
- **Features**: All new methods - whenAll(), whenAny(), whenNone(), timeout(), ChainConfig, ChainExtensionInterface
- **Use Case**: Complete demonstration of configuration, extensions, enhanced conditionals, timeout protection
- **Demonstrates**: Advanced integration scenarios with monitoring and performance optimization

## Fixtures

All examples use fixtures from `tests/fixtures/`. This ensures:
- **Consistency**: Same classes used in tests and examples
- **Maintainability**: Update fixture once, affects both tests and examples
- **Clarity**: No duplicate class definitions

Available fixtures:
- [`Account`](../tests/fixtures/Account.php) - Banking operations
- [`Calculator`](../tests/fixtures/Calculator.php) - Mathematical operations
- [`Counter`](../tests/fixtures/Counter.php) - Simple counter
- [`DataProcessor`](../tests/fixtures/DataProcessor.php) - Array processing
- [`EmailService`](../tests/fixtures/EmailService.php) - Email operations
- [`Logger`](../tests/fixtures/Logger.php) - Logging functionality
- [`NotificationService`](../tests/fixtures/NotificationService.php) - Notification operations
- [`Order`](../tests/fixtures/Order.php) - Order management
- [`Profile`](../tests/fixtures/Profile.php) - User profile
- [`Report`](../tests/fixtures/Report.php) - Report generation
- [`SimpleContainer`](../tests/fixtures/SimpleContainer.php) - PSR-11 container
- [`StringBuilder`](../tests/fixtures/StringBuilder.php) - String building
- [`User`](../tests/fixtures/User.php) - User entity with verification, premium, roles
- [`DummyClass`](../tests/fixtures/DummyClass.php) - Simple test fixture

## Feature Coverage

All Chain methods are demonstrated across examples:

**Core:**
- `of()` - All examples
- `get()`/`value()` - All examples
- `instance()` - workflow.php

**Transformation:**
- `tap()` - workflow.php, processing.php
- `map()` - workflow.php
- `pipe()` - workflow.php, pipeline.php, processing.php

**Enhanced Control Flow:**
- `whenAll()` - advanced-features.php
- `whenAny()` - advanced-features.php
- `whenNone()` - advanced-features.php
- `when()` - workflow.php, conditionals.php
- `unless()` - workflow.php, conditionals.php
- `clone()` - workflow.php, branching.php

**Enhanced Resilience:**
- `timeout()` - advanced-features.php
- `rescue()` - resilience.php
- `catch()` - resilience.php
- `retry()` - resilience.php

**Configuration & Extensions:**
- `ChainConfig` - advanced-features.php
- `ChainExtensionInterface` - advanced-features.php
- `addExtension()` - advanced-features.php

**Iteration & Debug:**
- `each()` - processing.php
- `dump()` - processing.php
- `dd()` - (use for debugging, not in production)

**Container:**
- `change()` - container.php
- `setResolver()` - container.php

## Adding New Examples

1. Create a new PHP file in `examples/`
2. Use existing fixtures from `tests/fixtures/`
3. Add clear comments explaining what features are demonstrated
4. Run `bash scripts/test-examples.sh` to verify it works
5. Update this README with a description
