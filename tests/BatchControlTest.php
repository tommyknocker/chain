<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use tommyknocker\chain\Batch;
use tommyknocker\chain\BatchEventType;
use tommyknocker\chain\FailureType;

final class BatchControlTest extends TestCase
{
    public function testFailFastValidationPreventsEveryTransferAndEmitsFailure(): void
    {
        $events = [];
        $report = Batch::fetch([
            'invalid' => 'not-a-url',
            'never-started' => 'https://example.test/never-started',
        ])->failFast()->onEvent(static function ($event) use (&$events): void {
            $events[] = $event;
        })->run();

        self::assertSame(FailureType::Validation, $report->failure('invalid')?->type());
        self::assertNull($report->failure('never-started'));
        self::assertCount(1, $events);
        self::assertSame(BatchEventType::Failed, $events[0]->type);
    }

    public function testSmallPositiveBatchTimeoutsRoundUpToOneMillisecond(): void
    {
        $batch = Batch::fetch([])->timeout(0.0001)->connectTimeout(0.0001);

        self::assertSame(1, new ReflectionProperty($batch, 'timeoutMs')->getValue($batch));
        self::assertSame(1, new ReflectionProperty($batch, 'connectTimeoutMs')->getValue($batch));
    }
}
