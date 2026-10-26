<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use tommyknocker\chain\Chain;
use tommyknocker\chain\ChainErrorExtensionInterface;
use tommyknocker\chain\ChainExtensionInterface;

final class ChainExtensionLifecycleTest extends TestCase
{
    public function testInvokeEmitsBeforeAndAfterEventsInOrder(): void
    {
        $events = [];
        $extension = new class ($events) implements ChainExtensionInterface {
            /** @param array<int, string> $events */
            public function __construct(public array &$events)
            {
            }

            public function beforeMethodCall(string $method, array $args): void
            {
                $this->events[] = "before:$method";
            }

            public function afterMethodCall(string $method, mixed $result): void
            {
                $this->events[] = "after:$method:$result";
            }
        };

        $result = Chain::of(new class () {
            public function get(): string
            {
                return 'result';
            }
        })->addExtension($extension)->invoke('get')->get();

        $this->assertSame('result', $result);
        $this->assertSame(['before:get', 'after:get:result'], $events);
    }

    public function testErrorExtensionReceivesTheOriginalWrappedMethodFailure(): void
    {
        $extension = new class () implements ChainExtensionInterface, ChainErrorExtensionInterface {
            public ?Throwable $error = null;

            public function beforeMethodCall(string $method, array $args): void
            {
            }

            public function afterMethodCall(string $method, mixed $result): void
            {
            }

            public function onMethodError(string $method, array $args, Throwable $error): void
            {
                $this->error = $error;
            }
        };

        $chain = Chain::of(new class () {
            public function fail(): never
            {
                throw new RuntimeException('failure');
            }
        })->addExtension($extension);

        try {
            $chain->invoke('fail');
            $this->fail('Expected the wrapped method failure.');
        } catch (RuntimeException $error) {
            $this->assertSame($error, $extension->error);
        }
    }

    public function testErrorObserverCannotMaskTheOriginalFailure(): void
    {
        $extension = new class () implements ChainExtensionInterface, ChainErrorExtensionInterface {
            public function beforeMethodCall(string $method, array $args): void
            {
            }

            public function afterMethodCall(string $method, mixed $result): void
            {
            }

            public function onMethodError(string $method, array $args, Throwable $error): void
            {
                throw new \LogicException('observer failure');
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('failure');

        Chain::of(new class () {
            public function fail(): never
            {
                throw new RuntimeException('failure');
            }
        })->addExtension($extension)->invoke('fail');
    }

    public function testCloneRetainsExtensionInstances(): void
    {
        $extension = new class () implements ChainExtensionInterface {
            public int $calls = 0;

            public function beforeMethodCall(string $method, array $args): void
            {
                $this->calls++;
            }

            public function afterMethodCall(string $method, mixed $result): void
            {
            }
        };

        $branch = Chain::of(new class () {
            public function run(): self
            {
                return $this;
            }
        })->addExtension($extension)->clone();

        $branch->invoke('run');

        $this->assertSame(1, $extension->calls);
    }
}
