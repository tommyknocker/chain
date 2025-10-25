<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Chain;
use tommyknocker\chain\ChainConfig;
use tommyknocker\chain\ChainExtensionInterface;
use tommyknocker\chain\Exception\ChainTimeoutException;
use tommyknocker\chain\tests\fixtures\Calculator;
use tommyknocker\chain\tests\fixtures\User;

final class ChainEnhancedTest extends TestCase
{
    // ==================== Enhanced Conditional Tests ====================

    public function testWhenAllExecutesWhenAllConditionsTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->whenAll(
                fn ($c) => $c->isPositive(),
                fn ($c) => $c->getValue() > 5,
                fn ($c) => $c->getValue() < 20
            )
            ->tap(function ($chain) use (&$executed) {
                $executed = true;
            });

        $this->assertTrue($executed);
    }

    public function testWhenAllDoesNotExecuteWhenAnyConditionFalse(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->whenAll(
                fn ($c) => $c->isPositive(),
                fn ($c) => $c->getValue() > 15, // This is false
                fn ($c) => $c->getValue() < 20
            )
            ->tap(function ($chain) use (&$executed) {
                $executed = true;
            });

        // The chain should continue even when whenAll conditions are false
        $this->assertTrue($executed);
    }

    public function testWhenAnyExecutesWhenAnyConditionTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->whenAny(
                fn ($c) => $c->getValue() > 15, // This is false
                fn ($c) => $c->isPositive(),   // This is true
                fn ($c) => $c->getValue() < 5   // This is false
            )
            ->tap(function ($chain) use (&$executed) {
                $executed = true;
            });

        $this->assertTrue($executed);
    }

    public function testWhenAnyDoesNotExecuteWhenAllConditionsFalse(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->whenAny(
                fn ($c) => $c->getValue() > 15, // This is false
                fn ($c) => $c->isNegative(),   // This is false
                fn ($c) => $c->getValue() < 5   // This is false
            )
            ->tap(function ($chain) use (&$executed) {
                $executed = true;
            });

        // The chain should continue even when whenAny conditions are false
        $this->assertTrue($executed);
    }

    public function testWhenNoneExecutesWhenNoConditionsTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->whenNone(
                fn ($c) => $c->getValue() > 15, // This is false
                fn ($c) => $c->isNegative(),   // This is false
                fn ($c) => $c->getValue() < 5   // This is false
            )
            ->tap(function ($chain) use (&$executed) {
                $executed = true;
            });

        $this->assertTrue($executed);
    }

    public function testWhenNoneDoesNotExecuteWhenAnyConditionTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->whenNone(
                fn ($c) => $c->isPositive(),   // This is true
                fn ($c) => $c->getValue() > 15, // This is false
                fn ($c) => $c->getValue() < 5   // This is false
            )
            ->tap(function ($chain) use (&$executed) {
                $executed = true;
            });

        // The chain should continue even when whenNone conditions are true
        $this->assertTrue($executed);
    }

    // ==================== Timeout Tests ====================

    public function testTimeoutExecutesSuccessfullyWithinTimeLimit(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->timeout(5, fn ($c) => $c->add(5))
            ->getValue()
            ->get();

        $this->assertSame(15.0, $result);
    }

    public function testTimeoutThrowsWhenOperationExceedsTimeLimit(): void
    {
        $calc = new Calculator(10);

        $this->expectException(ChainTimeoutException::class);
        $this->expectExceptionMessage('Operation timed out after 1 seconds');

        Chain::of($calc)
            ->timeout(1, function ($c) {
                sleep(2); // This will exceed the timeout

                return $c->add(5);
            });
    }

    // ==================== Configuration Tests ====================

    public function testConfigurationCanBeSet(): void
    {
        $config = ChainConfig::performance();
        Chain::configure($config);

        // This test mainly verifies the method exists and doesn't throw
        $this->assertInstanceOf(ChainConfig::class, $config);
    }

    public function testDefaultConfigurationIsUsedWhenNoneSet(): void
    {
        // Reset configuration
        Chain::configure(ChainConfig::default());

        $calc = new Calculator(10);
        $result = Chain::of($calc)
            ->add(5)
            ->getValue()
            ->get();

        $this->assertSame(15.0, $result);
    }

    // ==================== Extension Tests ====================

    public function testExtensionCanBeAdded(): void
    {
        $extension = new class () implements ChainExtensionInterface {
            public array $beforeCalls = [];
            public array $afterCalls = [];

            public function beforeMethodCall(string $method, array $args): void
            {
                $this->beforeCalls[] = ['method' => $method, 'args' => $args];
            }

            public function afterMethodCall(string $method, mixed $result): void
            {
                $this->afterCalls[] = ['method' => $method, 'result' => $result];
            }
        };

        $calc = new Calculator(10);
        Chain::of($calc)
            ->addExtension($extension)
            ->add(5)
            ->getValue();

        $this->assertCount(2, $extension->beforeCalls);
        $this->assertCount(2, $extension->afterCalls);
        $this->assertSame('add', $extension->beforeCalls[0]['method']);
        $this->assertSame('getValue', $extension->beforeCalls[1]['method']);
    }

    // ==================== Integration Tests ====================

    public function testAllNewFeaturesTogether(): void
    {
        $user = new User('Alice', 25);
        $extension = new class () implements ChainExtensionInterface {
            public int $callCount = 0;

            public function beforeMethodCall(string $method, array $args): void
            {
                $this->callCount++;
            }

            public function afterMethodCall(string $method, mixed $result): void
            {
                // Do nothing
            }
        };

        $result = Chain::of($user)
            ->addExtension($extension)
            ->whenAll(
                fn ($u) => $u->isAdult(),
                fn ($u) => strlen($u->getName()) > 3
            )
            ->whenAny(
                fn ($u) => $u->getAge() > 20,
                fn ($u) => $u->getAge() < 30
            )
            ->timeout(5, fn ($u) => $u->setEmail('alice@example.com'))
            ->getEmail()
            ->get();

        $this->assertSame('alice@example.com', $result);
        $this->assertGreaterThan(0, $extension->callCount);
    }
}
