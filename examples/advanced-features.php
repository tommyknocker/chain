<?php

require __DIR__ . '/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\ChainConfig;
use tommyknocker\chain\ChainExtensionInterface;
use tommyknocker\chain\tests\fixtures\Calculator;
use tommyknocker\chain\tests\fixtures\User;

/*
 * Advanced Chain Features Demo
 *
 * Demonstrates all the new enhanced features:
 * - Enhanced conditional methods (whenAll, whenAny, whenNone)
 * - Timeout protection
 * - Configuration system
 * - Extension system
 * - Method caching
 * - Specific exception types
 */

echo "\n🚀 Advanced Chain Features Demo\n";
echo str_repeat('=', 60) . "\n\n";

// ==================== Configuration Demo ====================

echo "1. Configuration System\n";
echo str_repeat('-', 60) . "\n";

// Set a default for subsequently created chains
Chain::configure(ChainConfig::performance());
echo "✓ Performance default enabled\n";

// Override settings for one chain without changing the default
$developmentChain = Chain::of(new Calculator(10))->withConfig(ChainConfig::development());
echo "✓ Development configuration applied to one chain\n\n";

// ==================== Extension System Demo ====================

echo "2. Extension System\n";
echo str_repeat('-', 60) . "\n";

class LoggingExtension implements ChainExtensionInterface
{
    private array $logs = [];

    public function beforeMethodCall(string $method, array $args): void
    {
        $this->logs[] = "Before: {$method}(" . implode(', ', $args) . ')';
    }

    public function afterMethodCall(string $method, mixed $result): void
    {
        $this->logs[] = "After: {$method} -> " . (is_object($result) ? get_class($result) : gettype($result));
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

echo "Calculation result: $result\n";
echo "Extension logs:\n";
foreach ($logger->getLogs() as $log) {
    echo "  - $log\n";
}
echo "\n";

// ==================== Enhanced Conditional Methods Demo ====================

echo "3. Enhanced Conditional Methods\n";
echo str_repeat('-', 60) . "\n";

$user = new User('Alice', 25);

// whenAll - all conditions must be true
$chain1 = Chain::of($user);
$chain1->whenAll(
    [
        fn ($u) => $u->isAdult(),
        fn ($u) => strlen($u->getName()) > 3,
        fn ($u) => $u->getAge() < 50,
    ],
    fn ($chain) => $chain->tap(fn ($u) => $u->setEmail('alice@example.com'))
);
$result1 = $chain1->getEmail()->get();

echo "whenAll result: $result1 (should be alice@example.com)\n";

// whenAny - any condition can be true
$chain2 = Chain::of($user);
$chain2->whenAny(
    [
        fn ($u) => $u->getAge() > 30,  // false
        fn ($u) => $u->isAdult(),      // true
        fn ($u) => $u->getAge() < 18,   // false
    ],
    fn ($chain) => $chain->tap(fn ($u) => $u->addRole('verified'))
);
$result2 = $chain2->getRoles()->get();

echo 'whenAny result: ' . implode(', ', $result2) . " (should include 'verified')\n";

// whenNone - no conditions should be true
$chain3 = Chain::of($user);
$chain3->whenNone(
    [
        fn ($u) => $u->getAge() > 30,  // false
        fn ($u) => $u->getAge() < 18,  // false
        fn ($u) => strlen($u->getName()) < 3,  // false
    ],
    fn ($chain) => $chain->tap(fn ($u) => $u->addRole('special'))
);
$result3 = $chain3->getRoles()->get();

echo 'whenNone result: ' . implode(', ', $result3) . " (should include 'special')\n\n";

// ==================== Timeout Protection Demo ====================

echo "4. Timeout Protection\n";
echo str_repeat('-', 60) . "\n";

try {
    $result = Chain::of(new Calculator(10))
        ->timeout(1, function ($calc) {
            // Simulate a slow operation
            usleep(500000); // 0.5 seconds

            return $calc->add(5);
        })
        ->getValue()
        ->get();

    echo "✓ Operation completed within timeout: $result\n";
} catch (\tommyknocker\chain\Exception\ChainTimeoutException $e) {
    echo '✗ Operation timed out: ' . $e->getMessage() . "\n";
}

try {
    $result = Chain::of(new Calculator(10))
        ->timeout(1, function ($calc) {
            // Simulate a very slow operation
            sleep(2); // 2 seconds

            return $calc->add(5);
        })
        ->getValue()
        ->get();

    echo "✓ Operation completed within timeout: $result\n";
} catch (\tommyknocker\chain\Exception\ChainTimeoutException $e) {
    echo '✗ Operation timed out: ' . $e->getMessage() . "\n";
}

echo "\n";

// ==================== Error Handling with Specific Exceptions ====================

echo "5. Enhanced Error Handling\n";
echo str_repeat('-', 60) . "\n";

try {
    Chain::of(new Calculator(10))
        ->nonExistentMethod();
} catch (\tommyknocker\chain\Exception\ChainMethodNotFoundException $e) {
    echo '✓ Caught specific exception: ' . $e->getMessage() . "\n";
}

try {
    Chain::of(new Calculator(10))
        ->map(fn ($c) => 'not-an-object');
} catch (\tommyknocker\chain\Exception\ChainInvalidOperationException $e) {
    echo '✓ Caught invalid operation exception: ' . $e->getMessage() . "\n";
}

echo "\n";

// ==================== Complex Integration Demo ====================

echo "6. Complex Integration Example\n";
echo str_repeat('-', 60) . "\n";

class PerformanceMonitor implements ChainExtensionInterface
{
    private array $timings = [];

    public function beforeMethodCall(string $method, array $args): void
    {
        $this->timings[$method] = microtime(true);
    }

    public function afterMethodCall(string $method, mixed $result): void
    {
        if (isset($this->timings[$method])) {
            $duration = (microtime(true) - $this->timings[$method]) * 1000;
            echo "  Method '$method' took " . round($duration, 2) . "ms\n";
        }
    }
}

$monitor = new PerformanceMonitor();

echo "Processing complex calculation with monitoring:\n";

$chain = Chain::of(new Calculator(100))->addExtension($monitor);
$chain->whenAll(
    [
        fn ($c) => $c->isPositive(),
        fn ($c) => $c->getValue() > 50,
    ],
    fn ($chain) => $chain->whenAny(
        [
            fn ($c) => $c->getValue() > 80,
            fn ($c) => $c->getValue() < 200,
        ],
        fn ($chain) => $chain->timeout(5, fn ($c) => $c->multiply(1.1))
    )
);
$chain->whenNone(
    [
        fn ($c) => $c->getValue() > 1000,
        fn ($c) => $c->getValue() < 0,
    ],
    fn ($chain) => $chain->pipe(
        fn ($c) => $c->subtract(10),
        fn ($c) => $c->multiply(0.9),
        fn ($c) => round($c->getValue(), 2)
    )
);
$finalResult = $chain->get();

echo "Final result: $finalResult\n\n";

// ==================== Summary ====================

echo str_repeat('=', 60) . "\n";
echo "✅ All advanced features demonstrated successfully!\n\n";

echo "New features showcased:\n";
echo "✓ ChainConfig - Configuration system\n";
echo "✓ ChainExtensionInterface - Extension system\n";
echo "✓ whenAll() - All conditions must be true\n";
echo "✓ whenAny() - Any condition can be true\n";
echo "✓ whenNone() - No conditions should be true\n";
echo "✓ timeout() - Timeout protection\n";
echo "✓ Specific exception types\n";
echo "✓ Method caching (performance optimization)\n";
echo "✓ Enhanced error handling\n";
echo "✓ Complex integration scenarios\n";

echo "\n" . str_repeat('=', 60) . "\n";
echo "🎉 Advanced Chain Features Demo Complete!\n\n";
