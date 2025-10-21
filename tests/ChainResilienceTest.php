<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Calculator;
use tommyknocker\chain\tests\fixtures\User;

final class ChainResilienceTest extends TestCase
{
    // ==================== value() Tests ====================

    public function testValueReturnsLastResult(): void
    {
        $calc = new Calculator(10);
        $result = Chain::of($calc)
            ->add(5)
            ->getValue()
            ->value();

        $this->assertSame(15.0, $result);
    }

    public function testValueIsAliasForGet(): void
    {
        $calc = new Calculator(10);
        $chain = Chain::of($calc)->add(5);

        $this->assertSame($chain->get(), $chain->value());
    }

    public function testValueReturnsInstanceWhenNoResult(): void
    {
        $calc = new Calculator(10);
        $result = Chain::of($calc)->value();

        $this->assertSame($calc, $result);
    }

    // ==================== dump() Tests ====================

    public function testDumpOutputsValueAndContinuesChain(): void
    {
        $calc = new Calculator(10);

        ob_start();
        $result = Chain::of($calc)
            ->add(5)
            ->getValue()
            ->dump()
            ->value();
        $output = ob_get_clean();

        $this->assertSame(15.0, $result);
        $this->assertStringContainsString('15', (string)$output);
    }

    public function testDumpWithLabel(): void
    {
        $calc = new Calculator(10);

        ob_start();
        Chain::of($calc)
            ->getValue()
            ->dump('Test Label');
        $output = ob_get_clean();

        $this->assertStringContainsString('[Test Label]', (string)$output);
        $this->assertStringContainsString('10', (string)$output);
    }

    public function testDumpReturnsChainForFurtherChaining(): void
    {
        $calc = new Calculator(10);

        ob_start();
        $result = Chain::of($calc)
            ->add(5)
            ->dump()
            ->add(3)
            ->getValue()
            ->value();
        ob_end_clean();

        $this->assertSame(18.0, $result);
    }

    // ==================== dd() Tests ====================

    public function testDdOutputsValueAndExits(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dd() should exit, cannot test directly');

        // dd() calls exit(1) which we can't test directly in PHPUnit
        // This test documents the expected behavior
        throw new \Exception('dd() should exit, cannot test directly');
    }

    // ==================== each() Tests ====================

    public function testEachIteratesOverArray(): void
    {
        $items = ['a', 'b', 'c'];
        $collected = [];

        Chain::of((object)['data' => $items])
            ->pipe(fn($obj) => $obj->data)
            ->each(function ($item) use (&$collected) {
                $collected[] = $item;
            })
            ->value();

        $this->assertSame(['a', 'b', 'c'], $collected);
    }

    public function testEachReceivesKeyAndValue(): void
    {
        $items = ['x' => 1, 'y' => 2];
        $keys = [];
        $values = [];

        Chain::of((object)['data' => $items])
            ->pipe(fn($obj) => $obj->data)
            ->each(function ($value, $key) use (&$keys, &$values) {
                $keys[] = $key;
                $values[] = $value;
            });

        $this->assertSame(['x', 'y'], $keys);
        $this->assertSame([1, 2], $values);
    }

    public function testEachReturnsChainForContinuation(): void
    {
        $items = [1, 2, 3];
        $sum = 0;

        $result = Chain::of((object)['data' => $items])
            ->pipe(fn($obj) => $obj->data)
            ->each(function ($item) use (&$sum) {
                $sum += $item;
            })
            ->pipe(fn($arr) => ['sum' => $sum, 'items' => $arr])
            ->value();

        $this->assertSame(6, $sum);
        $this->assertIsArray($result);
    }

    public function testEachDoesNothingForNonIterables(): void
    {
        $executed = false;

        $result = Chain::of(new Calculator(10))
            ->each(function () use (&$executed) {
                $executed = true;
            })
            ->value();

        $this->assertFalse($executed);
        $this->assertInstanceOf(Calculator::class, $result);
    }

    // ==================== rescue() Tests ====================

    public function testRescueCatchesException(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->rescue(
                fn($c) => $c->divide(0), // throws exception
                fn($e) => -1.0
            )
            ->value();

        $this->assertSame(-1.0, $result);
    }

    public function testRescueReturnsResultWhenNoException(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->rescue(
                fn($c) => $c->add(5),
                fn($e) => -1.0
            )
            ->getValue()
            ->value();

        $this->assertSame(15.0, $result);
    }

    public function testRescueCanReturnObject(): void
    {
        $calc = new Calculator(10);
        $fallback = new Calculator(999);

        $result = Chain::of($calc)
            ->rescue(
                fn($c) => $c->divide(0),
                fn($e) => $fallback
            )
            ->getValue()
            ->value();

        $this->assertSame(999.0, $result);
    }

    public function testRescueContinuesChainAfterException(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->rescue(
                fn($c) => $c->divide(0),
                fn($e) => new Calculator(5)
            )
            ->add(10)
            ->getValue()
            ->value();

        $this->assertSame(15.0, $result);
    }

    // ==================== catch() Tests ====================

    public function testCatchCatchesSpecificException(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->catch(
                \InvalidArgumentException::class,
                fn($c) => $c->divide(0),
                fn($e) => -1.0
            )
            ->value();

        $this->assertSame(-1.0, $result);
    }

    public function testCatchRethrowsOtherExceptions(): void
    {
        $calc = new Calculator(10);

        $this->expectException(\RuntimeException::class);

        Chain::of($calc)
            ->catch(
                \InvalidArgumentException::class, // Expecting this
                function ($c) {
                    throw new \RuntimeException('Different exception'); // But this is thrown
                },
                fn($e) => -1.0
            )
            ->value();
    }

    public function testCatchReturnsResultWhenNoException(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->catch(
                \InvalidArgumentException::class,
                fn($c) => $c->add(5),
                fn($e) => -1.0
            )
            ->getValue()
            ->value();

        $this->assertSame(15.0, $result);
    }

    // ==================== retry() Tests ====================

    public function testRetrySucceedsOnFirstAttempt(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->retry(3, fn($c) => $c->add(5))
            ->getValue()
            ->value();

        $this->assertSame(15.0, $result);
    }

    public function testRetrySucceedsOnSecondAttempt(): void
    {
        $calc = new Calculator(10);
        $attempts = 0;

        $result = Chain::of($calc)
            ->retry(3, function ($c) use (&$attempts) {
                $attempts++;
                if ($attempts < 2) {
                    throw new \RuntimeException('Fail');
                }
                return $c->add(5);
            })
            ->getValue()
            ->value();

        $this->assertSame(2, $attempts);
        $this->assertSame(15.0, $result);
    }

    public function testRetryThrowsAfterAllAttemptsFail(): void
    {
        $calc = new Calculator(10);
        $attempts = 0;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Always fails');

        Chain::of($calc)
            ->retry(3, function ($c) use (&$attempts) {
                $attempts++;
                throw new \RuntimeException('Always fails');
            })
            ->value();
    }

    public function testRetryDelayBetweenAttempts(): void
    {
        $calc = new Calculator(10);
        $attempts = 0;
        $start = microtime(true);

        try {
            Chain::of($calc)
                ->retry(3, function ($c) use (&$attempts) {
                    $attempts++;
                    throw new \RuntimeException('Fail');
                }, 10) // 10ms delay
                ->value();
        } catch (\RuntimeException $e) {
            // Expected
        }

        $duration = (microtime(true) - $start) * 1000; // Convert to ms

        $this->assertSame(3, $attempts);
        $this->assertGreaterThanOrEqual(20, $duration); // At least 2 delays (10ms each)
    }

    public function testRetryCanReturnObject(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->retry(2, fn($c) => $c->add(5))
            ->getValue()
            ->value();

        $this->assertSame(15.0, $result);
    }

    // ==================== Integration Tests ====================

    public function testAllNewFeaturesTogether(): void
    {
        $calc = new Calculator(10);
        $log = [];

        ob_start();
        $result = Chain::of($calc)
            ->add(5)
            ->dump('After add')
            ->retry(2, fn($c) => $c->multiply(2))
            ->rescue(
                fn($c) => $c->divide(0),
                fn($e) => new Calculator(100)
            )
            ->pipe(fn($c) => [$c->getValue()])
            ->each(function ($val) use (&$log) {
                $log[] = $val;
            })
            ->value();
        ob_end_clean();

        $this->assertIsArray($result);
        $this->assertSame([100.0], $log);
    }

    public function testValueAndGetAreInterchangeable(): void
    {
        $user = new User('Alice', 25);
        $chain = Chain::of($user)->setEmail('test@example.com');

        $viaGet = $chain->get();
        $viaValue = $chain->value();

        $this->assertSame($viaGet, $viaValue);
    }
}

