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

        Chain::of($calc)->whenAll(
            [
                fn ($c) => $c->isPositive(),
                fn ($c) => $c->getValue() > 5,
                fn ($c) => $c->getValue() < 20,
            ],
            function ($chain) use (&$executed): void {
                $executed = true;
            }
        );

        $this->assertTrue($executed);
    }

    public function testWhenAllDoesNotExecuteWhenAnyConditionFalse(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)->whenAll(
            [
                fn ($c) => $c->isPositive(),
                fn ($c) => $c->getValue() > 15,
                fn ($c) => $c->getValue() < 20,
            ],
            function ($chain) use (&$executed): void {
                $executed = true;
            }
        );

        $this->assertFalse($executed);
    }

    public function testWhenAnyExecutesWhenAnyConditionTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)->whenAny(
            [
                fn ($c) => $c->getValue() > 15,
                fn ($c) => $c->isPositive(),
                fn ($c) => $c->getValue() < 5,
            ],
            function ($chain) use (&$executed): void {
                $executed = true;
            }
        );

        $this->assertTrue($executed);
    }

    public function testWhenAnyDoesNotExecuteWhenAllConditionsFalse(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)->whenAny(
            [
                fn ($c) => $c->getValue() > 15,
                fn ($c) => $c->isNegative(),
                fn ($c) => $c->getValue() < 5,
            ],
            function ($chain) use (&$executed): void {
                $executed = true;
            }
        );

        $this->assertFalse($executed);
    }

    public function testWhenNoneExecutesWhenNoConditionsTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)->whenNone(
            [
                fn ($c) => $c->getValue() > 15,
                fn ($c) => $c->isNegative(),
                fn ($c) => $c->getValue() < 5,
            ],
            function ($chain) use (&$executed): void {
                $executed = true;
            }
        );

        $this->assertTrue($executed);
    }

    public function testWhenNoneDoesNotExecuteWhenAnyConditionTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)->whenNone(
            [
                fn ($c) => $c->isPositive(),
                fn ($c) => $c->getValue() > 15,
                fn ($c) => $c->getValue() < 5,
            ],
            function ($chain) use (&$executed): void {
                $executed = true;
            }
        );

        $this->assertFalse($executed);
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

    public function testTimeoutInterruptsTheCallbackBeforeItsSideEffect(): void
    {
        $value = new \stdClass();
        $value->completed = false;

        try {
            Chain::of($value)->timeout(1, function (\stdClass $value): \stdClass {
                sleep(2);
                $value->completed = true;

                return $value;
            });
            $this->fail('Expected a timeout.');
        } catch (ChainTimeoutException) {
            $this->assertFalse($value->completed);
        }
    }

    // ==================== Configuration Tests ====================

    public function testConfigurationCanBeSet(): void
    {
        Chain::configure(new ChainConfig(maxRetryAttempts: 2, defaultRetryDelay: 0));
        $chain = Chain::of(new Calculator(10));
        Chain::configure(new ChainConfig(maxRetryAttempts: 1, defaultRetryDelay: 0));
        $attempts = 0;

        $chain->retry(null, function (Calculator $calculator) use (&$attempts): Calculator {
            $attempts++;
            if ($attempts === 1) {
                throw new \RuntimeException('Retry once');
            }

            return $calculator;
        });

        $this->assertSame(2, $attempts);
        Chain::configure(ChainConfig::default());
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

    public function testConfigurationProvidesRetryDefaults(): void
    {
        $attempts = 0;

        $result = Chain::of(new Calculator(10))
            ->withConfig(new ChainConfig(maxRetryAttempts: 2, defaultRetryDelay: 0))
            ->retry(null, function (Calculator $calculator) use (&$attempts): Calculator {
                $attempts++;
                if ($attempts === 1) {
                    throw new \RuntimeException('Retry once');
                }

                return $calculator->add(5);
            })
            ->getValue()
            ->get();

        $this->assertSame(2, $attempts);
        $this->assertSame(15.0, $result);
    }

    public function testDeepCloneConfigurationClonesNestedObjects(): void
    {
        Chain::configure(new ChainConfig(enableDeepCloning: true));
        $object = new \stdClass();
        $object->child = new \stdClass();
        $object->child->value = 1;

        $clone = Chain::of($object)->clone()->instance();
        $clone->child->value = 2;

        $this->assertNotSame($object->child, $clone->child);
        $this->assertSame(1, $object->child->value);

        Chain::configure(ChainConfig::default());
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

        $chain = Chain::of($user)->addExtension($extension);
        $result = $chain->whenAll(
            [
                fn ($u) => $u->isAdult(),
                fn ($u) => strlen($u->getName()) > 3,
            ],
            fn ($chain) => $chain->whenAny(
                [
                fn ($u) => $u->getAge() > 20,
                fn ($u) => $u->getAge() < 30,
                ],
                fn ($chain) => $chain->timeout(5, fn ($u) => $u->setEmail('alice@example.com'))
            )
        )
            ->getEmail()
            ->get();

        $this->assertSame('alice@example.com', $result);
        $this->assertGreaterThan(0, $extension->callCount);
    }
}
