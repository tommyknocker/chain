# Test Fixtures

This directory contains reusable test fixtures used across both tests and examples.

## Philosophy

All fixtures are shared between:
- **Tests** (`tests/*.php`)
- **Examples** (`examples/*.php`)

This ensures consistency and easier maintenance - update once, affects everywhere.

## Available Fixtures

### Banking & Finance

#### [`Account.php`](Account.php)
Banking account operations.
```php
$account = new Account($initialBalance = 0);
$account->deposit(float $amount): self
$account->withdraw(float $amount): self
$account->getBalance(): float
$account->addBonus(): self  // Adds 100
$account->applyInterest(float $rate = 0.05): self
```

**Used in:**
- [tests/ChainNewMethodsTest.php](../ChainNewMethodsTest.php) - conditional tests
- [examples/conditional.php](../../examples/conditional.php)
- [examples/conditional-logic.php](../../examples/conditional-logic.php)
- [examples/immutable-branching.php](../../examples/immutable-branching.php)

#### [`Order.php`](Order.php)
Order management with tax and discounts.
```php
$order = new Order(float $total);
$order->getTotal(): float
$order->addTax(float $rate = 0.1): self
$order->applyDiscount(float $amount): self
```

**Used in:**
- [examples/basic.php](../../examples/basic.php)
- [examples/quick-example.php](../../examples/quick-example.php)

### Math & Calculations

#### [`Calculator.php`](Calculator.php)
Mathematical operations.
```php
$calc = new Calculator($initialValue = 0);
$calc->add(float $n): self
$calc->subtract(float $n): self
$calc->multiply(float $n): self
$calc->divide(float $n): self
$calc->getValue(): float
$calc->isPositive(): bool
$calc->isNegative(): bool
```

**Used in:**
- [tests/ChainNewMethodsTest.php](../ChainNewMethodsTest.php) - core functionality
- [examples/pipeline.php](../../examples/pipeline.php)
- [examples/branching-scenarios.php](../../examples/branching-scenarios.php)

#### [`Counter.php`](Counter.php)
Simple counter.
```php
$counter = new Counter($initialValue = 0);
$counter->inc(int $n = 1): self
$counter->dec(int $n = 1): self
$counter->getValue(): int
$counter->reset(): self
```

**Used in:**
- [examples/branching.php](../../examples/branching.php)

### Data Processing

#### [`DataProcessor.php`](DataProcessor.php)
Array data processing.
```php
$processor = new DataProcessor(array $data = []);
$processor->addItem(mixed $item): self
$processor->filter(callable $fn): self
$processor->transform(callable $fn): self
$processor->getData(): array
$processor->sum(): float
$processor->count(): int
```

**Used in:**
- [examples/complex-workflow.php](../../examples/complex-workflow.php)

#### [`Report.php`](Report.php)
Report generation.
```php
$report = new Report(float $total, int $count);
$report->format(): string
$report->getTotal(): float
$report->getCount(): int
$report->getAverage(): float
```

**Used in:**
- [examples/complex-workflow.php](../../examples/complex-workflow.php)

### String & Text

#### [`StringBuilder.php`](StringBuilder.php)
String building operations.
```php
$builder = new StringBuilder(string $initial = '');
$builder->append(string $str): self
$builder->prepend(string $str): self
$builder->uppercase(): self
$builder->lowercase(): self
$builder->toString(): string
```

**Used in:**
- [examples/make.php](../../examples/make.php)

### Logging & Notifications

#### [`Logger.php`](Logger.php)
Logging functionality.
```php
$logger = new Logger();
$logger->log(string $message): self
$logger->getLogs(): array
$logger->clear(): self
$logger->count(): int
```

**Used in:**
- [examples/tap.php](../../examples/tap.php)

#### [`EmailService.php`](EmailService.php)
Email operations.
```php
$service = new EmailService();
$service->send(string $to): string
$service->sendBulk(array $recipients): string
```

**Used in:**
- [examples/container.php](../../examples/container.php)

#### [`NotificationService.php`](NotificationService.php)
Notification operations.
```php
$service = new NotificationService();
$service->notify(string $message): string
$service->notifyUrgent(string $message): string
```

**Used in:**
- [examples/container.php](../../examples/container.php)

### Users & Entities

#### [`User.php`](User.php)
User entity with properties, verification, premium status, and roles.
```php
$user = new User(string $name, int $age);
$user->getName(): string
$user->getAge(): int
$user->setAdmin(bool $isAdmin): self
$user->isAdmin(): bool
$user->setEmail(string $email): self
$user->getEmail(): ?string
$user->isAdult(): bool
$user->verify(): self
$user->isVerified(): bool
$user->upgradeToPremium(): self
$user->isPremium(): bool
$user->addRole(string $role): self
$user->getRoles(): array
```

**Used in:**
- [tests/ChainNewMethodsTest.php](../ChainNewMethodsTest.php)
- [examples/basic.php](../../examples/basic.php)
- [examples/quick-example.php](../../examples/quick-example.php)
- [examples/user-profile-workflow.php](../../examples/user-profile-workflow.php)

#### [`Profile.php`](Profile.php)
User profile with bio, avatar, preferences, and notifications.
```php
$profile = new Profile(User $user);
$profile->getUser(): User
$profile->setBio(string $bio): self
$profile->getBio(): string
$profile->setAvatar(string $url): self
$profile->getAvatar(): string
$profile->setPreference(string $key, mixed $value): self
$profile->getPreference(string $key): mixed
$profile->getPreferences(): array
$profile->enablePremiumFeatures(): self
$profile->addNotification(string $message): self
$profile->getNotifications(): array
$profile->getCompleteness(): int
$profile->toArray(): array
```

**Used in:**
- [examples/user-profile-workflow.php](../../examples/user-profile-workflow.php)

### Utilities

#### [`DummyClass.php`](DummyClass.php)
Simple test fixture for basic testing.
```php
$dummy = new DummyClass(string $value);
$dummy->getValue(): string
```

**Used in:**
- [tests/ChainTest.php](../ChainTest.php)
- [tests/ChainNewMethodsTest.php](../ChainNewMethodsTest.php)

#### [`SimpleContainer.php`](SimpleContainer.php)
PSR-11 container implementation.
```php
$container = new SimpleContainer();
$container->set(string $id, object $service): void
$container->get(string $id): object
$container->has(string $id): bool
```

**Used in:**
- [tests/ChainTest.php](../ChainTest.php)
- [examples/container.php](../../examples/container.php)

## Adding New Fixtures

1. Create a new fixture class in this directory
2. Use `final class` and `declare(strict_types=1)` 
3. Keep methods fluent (return `self`) where appropriate
4. Add namespace: `namespace tommyknocker\chain\tests\fixtures;`
5. Document in this README with usage examples
6. Link to tests/examples that use it

## Best Practices

- **Fluent API**: Most methods return `self` for method chaining
- **Type Safety**: All fixtures use strict types
- **Simplicity**: Keep fixtures simple and focused
- **Reusability**: Design for use in both tests and examples
- **Documentation**: Clear method signatures and purposes

