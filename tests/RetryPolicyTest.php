<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\chain\Chain;
use tommyknocker\chain\RetryAttempt;
use tommyknocker\chain\RetryPolicy;
use tommyknocker\chain\SleeperInterface;

final class RetryPolicyTest extends TestCase
{
    public function testPolicyAppliesBackoffAndDelayCap(): void
    {
        $policy = new RetryPolicy(initialDelayMs: 10, backoffMultiplier: 2, maxDelayMs: 25);

        $this->assertSame(10, $policy->delayAfter(1));
        $this->assertSame(20, $policy->delayAfter(2));
        $this->assertSame(25, $policy->delayAfter(3));
    }

    public function testRetryWithProvidesMetadataAndUsesInjectedSleeper(): void
    {
        $sleeper = new class () implements SleeperInterface {
            /** @var list<int> */
            public array $delays = [];

            public function sleep(int $milliseconds): void
            {
                $this->delays[] = $milliseconds;
            }
        };
        $attempts = [];

        $result = Chain::of(new \stdClass())
            ->withSleeper($sleeper)
            ->retryWith(new RetryPolicy(maxAttempts: 3, initialDelayMs: 10, backoffMultiplier: 2), function (object $object, RetryAttempt $attempt) use (&$attempts): string {
                $attempts[] = [$attempt->number, $attempt->previousError?->getMessage()];
                if ($attempt->number < 3) {
                    throw new RuntimeException('temporary');
                }

                return 'complete';
            })
            ->get();

        $this->assertSame('complete', $result);
        $this->assertSame([[1, null], [2, 'temporary'], [3, 'temporary']], $attempts);
        $this->assertSame([10, 20], $sleeper->delays);
    }

    public function testRetryPredicateStopsOnPermanentErrors(): void
    {
        $attempts = 0;

        try {
            Chain::of(new \stdClass())->retryWith(
                new RetryPolicy(maxAttempts: 3, retryIf: static fn (\Throwable $error): bool => false),
                function () use (&$attempts): never {
                    $attempts++;

                    throw new RuntimeException('permanent');
                }
            );
            $this->fail('Expected the permanent error.');
        } catch (RuntimeException $error) {
            $this->assertSame('permanent', $error->getMessage());
            $this->assertSame(1, $attempts);
        }
    }
}
