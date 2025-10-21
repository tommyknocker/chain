<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Calculator;

/**
 * Error handling and resilience features
 * Demonstrates: rescue(), catch(), retry()
 */

echo "\nResilience & Error Handling Examples\n";
echo str_repeat("=", 60) . "\n\n";

// ==================== rescue() - General Exception Handling ====================

echo "1. rescue() - Handle any exception with fallback\n";
echo str_repeat("-", 60) . "\n";

$result = Chain::of(new Calculator(100))
    ->rescue(
        callback: fn($calc) => $calc->divide(0),
        handler: function ($e) {
            echo "Caught: {$e->getMessage()}\n";
            echo "Returning fallback\n";
            return new Calculator(0);
        }
    )
    ->getValue()
    ->value();

echo "Result: $result\n\n";

// ==================== catch() - Specific Exception Types ====================

echo "2. catch() - Handle specific exception types\n";
echo str_repeat("-", 60) . "\n";

$result = Chain::of(new Calculator(50))
    ->catch(
        exceptionClass: \InvalidArgumentException::class,
        callback: fn($calc) => $calc->divide(0),
        handler: fn($e) => new Calculator(10)
    )
    ->multiply(5)
    ->getValue()
    ->value();

echo "Result after catch: $result\n\n";

// ==================== retry() - Automatic Retries ====================

echo "3. retry() - Retry failed operations\n";
echo str_repeat("-", 60) . "\n";

$attempts = 0;

$result = Chain::of(new Calculator(10))
    ->retry(
        times: 3,
        callback: function ($calc) use (&$attempts) {
            $attempts++;
            echo "Attempt $attempts...\n";
            
            if ($attempts < 3) {
                throw new \RuntimeException("Temporary failure");
            }
            
            return $calc->add(90);
        },
        delayMs: 50
    )
    ->getValue()
    ->value();

echo "Success on attempt $attempts: $result\n\n";

// ==================== Real-World: API Client ====================

echo "4. Real-world: Resilient API calls\n";
echo str_repeat("-", 60) . "\n";

class ApiClient
{
    private int $calls = 0;
    
    public function fetch(): array
    {
        $this->calls++;
        if ($this->calls < 2) {
            throw new \RuntimeException("Network error");
        }
        return ['users' => 100];
    }
}

$data = Chain::of(new ApiClient())
    ->retry(
        times: 3,
        callback: fn($api) => $api->fetch(),
        delayMs: 100
    )
    ->value();

echo "Fetched data: {$data['users']} users\n\n";

echo str_repeat("=", 60) . "\n";
echo "✓ rescue() - handle any exception\n";
echo "✓ catch() - handle specific exceptions\n";
echo "✓ retry() - automatic retries with backoff\n";
echo str_repeat("=", 60) . "\n\n";

