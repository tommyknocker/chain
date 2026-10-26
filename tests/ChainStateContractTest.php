<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Chain;

final class ChainStateContractTest extends TestCase
{
    public function testScalarPipelineKeepsTheObjectReceiverUntilAnObjectReplacesIt(): void
    {
        $source = new class () {
            public function number(): int
            {
                return 2;
            }
        };
        $target = new class () {
            public function label(): string
            {
                return 'target';
            }
        };

        $chain = Chain::of($source)->number()->pipe(fn (int $value): int => $value * 3);

        $this->assertSame($source, $chain->instance());
        $this->assertSame(6, $chain->get());

        $chain->map(fn (int $value) => $target);

        $this->assertSame($target, $chain->instance());
        $this->assertSame($target, $chain->get());
        $this->assertSame('target', $chain->invoke('label')->get());
    }

    public function testNullIsARealCurrentValueAcrossRescueAndPipe(): void
    {
        $source = new \stdClass();

        $chain = Chain::of($source)
            ->rescue(
                static fn (): never => throw new \RuntimeException('failure'),
                static fn (): null => null
            )
            ->pipe(static fn (?object $value): string => $value === null ? 'null preserved' : 'unexpected');

        $this->assertSame($source, $chain->instance());
        $this->assertSame('null preserved', $chain->get());
    }

    public function testChangeResetsBothPartsOfStateBeforeTheNextInvocation(): void
    {
        $replacement = new class () {
            public function value(): string
            {
                return 'replacement';
            }
        };

        $chain = Chain::of(new class () {
            public function value(): string
            {
                return 'source';
            }
        })->invoke('value')->change($replacement);

        $this->assertSame($replacement, $chain->instance());
        $this->assertSame($replacement, $chain->get());
        $this->assertSame('replacement', $chain->invoke('value')->get());
    }
}
