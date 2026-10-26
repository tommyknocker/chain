# Chain examples

### Basic Chaining
```php
// Create instance and chain
$result = Chain::of(new User('Alice', 25))
    ->map(fn(User $user) => new Order(strlen($user->getName()) * 10))
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
$chain = Chain::of(new User('Alice', 25));
$chain->whenAll(
    [
        fn($u) => $u->isAdult(),
        fn($u) => strlen($u->getName()) > 3,
        fn($u) => $u->getAge() < 50
    ],
    fn($chain) => $chain->tap(fn($u) => $u->setEmail('alice@example.com'))
);
$result = $chain->getEmail()->get();

// Any condition can be true
$chain = Chain::of(new User('Bob', 16));
$chain->whenAny(
    [
        fn($u) => $u->isAdult(),
        fn($u) => $u->getAge() > 15,
        fn($u) => strlen($u->getName()) > 2
    ],
    fn($chain) => $chain->tap(fn($u) => $u->addRole('verified'))
);
$result = $chain->getRoles()->get();

// No conditions should be true
$chain = Chain::of(new User('Charlie', 25));
$chain->whenNone(
    [
        fn($u) => $u->getAge() > 30,
        fn($u) => $u->getAge() < 18,
        fn($u) => strlen($u->getName()) < 3
    ],
    fn($chain) => $chain->tap(fn($u) => $u->addRole('special'))
);
$result = $chain->getRoles()->get();
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
    ->invoke('filter', fn($x) => $x >= 100)  // Call DataProcessor::filter()
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
// Set default behavior for chains created after this call
Chain::configure(ChainConfig::performance());

// Override settings for one chain without changing other chains
$result = Chain::of(new Calculator(10))
    ->withConfig(ChainConfig::development())
    ->retry(null, fn($calculator) => $calculator->add(5))
    ->getValue()
    ->get();

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

// Set a resolver default during bootstrap
Chain::setResolver($container);

// Switch between services dynamically
$result1 = Chain::of($container->get('email'))
    ->send('user@example.com')
    ->get();

$result2 = Chain::of($container->get('email'))
    ->withResolver($container) // Optional local override for this chain
    ->change('notification')  // Switch to notification service
    ->notify('Important update')
    ->get();

echo "$result1\n";  // "Email sent to user@example.com"
echo "$result2\n";  // "Notification: Important update"
```
