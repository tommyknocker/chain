<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Flow;
use tommyknocker\chain\FlowContext;
use tommyknocker\chain\FlowEventType;
use tommyknocker\chain\FlowStop;
use tommyknocker\chain\StepInterface;
use tommyknocker\chain\tests\fixtures\SimpleContainer;

final class FlowTest extends TestCase
{
    public function testFlowRunsCallablesAndIsReusable(): void
    {
        $base = Flow::of(static fn (int $value): int => $value + 1);

        self::assertSame(2, $base->run(1));
        self::assertSame(4, $base->then(static fn (int $value): int => $value * 2)->run(1));
    }

    public function testFlowRunsStepClassesAndCanStop(): void
    {
        $flow = Flow::of(new class () implements StepInterface {
            public function handle(mixed $payload, FlowContext $context): mixed
            {
                return Flow::stop($payload + 1);
            }
        }, static fn (): never => throw new \RuntimeException('must not run'));

        $execution = $flow->execute(1);

        self::assertTrue($execution->stopped());
        self::assertSame(2, $execution->result());
        self::assertCount(1, $execution->steps());
    }

    public function testNestedFlowPropagatesItsStopToTheOuterFlow(): void
    {
        $nested = Flow::of(static fn (int $value): FlowStop => Flow::stop($value + 1));
        $flow = Flow::of($nested, static fn (): never => throw new \RuntimeException('must not run'));

        $execution = $flow->execute(1);

        self::assertTrue($execution->stopped());
        self::assertSame(2, $execution->result());
        self::assertCount(1, $execution->steps());
    }

    public function testNestedFlowInheritsContextAndItsOwnValuesTakePrecedence(): void
    {
        $nested = Flow::of(static fn (mixed $payload, FlowContext $context): array => [
            $context->get('traceId'),
            $context->get('source'),
        ])->withContext(['source' => 'nested']);

        $result = Flow::of($nested)
            ->withContext(['traceId' => 'checkout-42', 'source' => 'outer'])
            ->run(null);

        self::assertSame(['checkout-42', 'nested'], $result);
    }

    public function testNestedFlowPrefersItsOwnResolverAndResolvesClassStringSteps(): void
    {
        $parent = new SimpleContainer();
        $parent->set(FlowContextValueStep::class, new FlowContextValueStep('parent'));
        $child = new SimpleContainer();
        $child->set(FlowContextValueStep::class, new FlowContextValueStep('child'));

        $result = Flow::of(
            Flow::of(FlowContextValueStep::class)->withResolver($child),
        )->withResolver($parent)->run(null);

        self::assertSame('child', $result);
    }

    public function testWhenRunsOnlyTheSelectedNestedFlow(): void
    {
        $flow = Flow::start()
            ->when(static fn (int $value): bool => $value > 0, Flow::of(static fn (int $value): int => $value + 1));

        self::assertSame(2, $flow->run(1));
        self::assertSame(0, $flow->run(0));
    }

    public function testExecutionCapturesStepFailureAndRunRethrowsIt(): void
    {
        $flow = Flow::of(static fn (): never => throw new \RuntimeException('broken'));

        self::assertTrue($flow->execute('input')->failed());
        $this->expectExceptionMessage('Flow failed at step 0');
        $flow->run('input');
    }

    public function testEventObserversDoNotChangeFlowResult(): void
    {
        $events = [];
        $result = Flow::of(static fn (int $value): int => $value + 1)
            ->onEvent(function ($event) use (&$events): void {
                $events[] = $event->type;

                throw new \RuntimeException('observer failure');
            })
            ->run(1);

        self::assertSame(2, $result);
        self::assertSame([FlowEventType::Started, FlowEventType::Completed], $events);
    }
}

final class FlowContextValueStep implements StepInterface
{
    public function __construct(private readonly string $value)
    {
    }

    public function handle(mixed $payload, FlowContext $context): mixed
    {
        return $this->value;
    }
}
