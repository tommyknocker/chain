<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Chain;
use tommyknocker\chain\Exception\ChainInvalidOperationException;

final class ValueCombinatorTest extends TestCase
{
    public function testInspectLeavesCurrentValueUnchanged(): void
    {
        $seen = null;

        $result = Chain::of(new \stdClass())
            ->pipe(static fn (): array => [1, 2])
            ->inspect(function (array $value) use (&$seen): void {
                $seen = $value;
            })
            ->get();

        $this->assertSame([1, 2], $seen);
        $this->assertSame([1, 2], $result);
    }

    public function testFilterPreservesKeysAndObjectReceiver(): void
    {
        $object = new \stdClass();
        $chain = Chain::of($object)
            ->pipe(static fn (): array => ['a' => 1, 'b' => 2, 'c' => 3])
            ->filter(static fn (int $value): bool => $value % 2 === 1);

        $this->assertSame($object, $chain->instance());
        $this->assertSame(['a' => 1, 'c' => 3], $chain->get());
    }

    public function testFlatMapFlattensIterableResults(): void
    {
        $result = Chain::of(new \stdClass())
            ->pipe(static fn (): array => [1, 2])
            ->flatMap(static fn (int $value): array => [$value, $value * 10])
            ->get();

        $this->assertSame([1, 10, 2, 20], $result);
    }

    public function testRecoverUsesFallbackForValueCallbackFailure(): void
    {
        $result = Chain::of(new \stdClass())
            ->pipe(static fn (): string => 'input')
            ->recover(
                static fn (): never => throw new \RuntimeException('failure'),
                static fn (\Throwable $error): string => $error->getMessage()
            )
            ->get();

        $this->assertSame('failure', $result);
    }

    public function testCollectionCombinatorsRejectScalars(): void
    {
        $this->expectException(ChainInvalidOperationException::class);

        Chain::of(new \stdClass())->pipe(static fn (): int => 1)->filter(static fn (): bool => true);
    }
}
