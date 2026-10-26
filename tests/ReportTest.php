<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Download;
use tommyknocker\chain\Failure;
use tommyknocker\chain\Report;
use tommyknocker\chain\Response;

final class ReportTest extends TestCase
{
    public function testReportSeparatesBodiesDownloadsAndFailures(): void
    {
        $report = new Report();
        $report->addSuccess(new Response('text', 'https://example.test/text', 200, [], 0.1, 'hello'));
        $report->addSuccess(new Download('image', 'https://example.test/image', 200, [], 0.2, '/tmp/image.jpg', 10));
        $report->addFailure(new Failure('missing', 'https://example.test/missing', 'validation', 'Unexpected HTTP status 404.', null, 404));

        self::assertSame(['text' => 'hello'], $report->bodies());
        self::assertSame(['text'], array_keys($report->responses()));
        self::assertSame(['image'], array_keys($report->downloads()));
        self::assertSame(404, $report->failure('missing')?->status());
        self::assertTrue($report->hasFailures());
    }
}
