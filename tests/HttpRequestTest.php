<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use tommyknocker\chain\HttpRequest;

final class HttpRequestTest extends TestCase
{
    public function testHeadersPreserveMultipleValidatedValues(): void
    {
        $request = HttpRequest::get('https://example.test')->headers([
            'Accept' => ['application/json', 'application/problem+json'],
        ]);

        self::assertSame(['application/json', 'application/problem+json'], $request->headerValues()['accept']['values']);
    }

    public function testHeadersRejectNewlinesInEveryValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HttpRequest::get('https://example.test')->headers([
            'Accept' => ['application/json', "invalid\r\nInjected: value"],
        ]);
    }

    public function testQueryAddsParametersWithoutMutatingOriginalRequest(): void
    {
        $request = HttpRequest::get('https://example.test/items')->query(['page' => 2]);

        self::assertSame('https://example.test/items?page=2', $request->url());
        self::assertSame('https://example.test/items', HttpRequest::get('https://example.test/items')->url());
    }

    public function testQueryIsInsertedBeforeTheFragment(): void
    {
        $request = HttpRequest::get('https://example.test/items?sort=name#section')->query(['page' => 2]);

        self::assertSame('https://example.test/items?sort=name&page=2#section', $request->url());
    }

    public function testSmallPositiveTimeoutsRoundUpToOneMillisecond(): void
    {
        $request = HttpRequest::get('https://example.test')
            ->timeout(0.0001)
            ->connectTimeout(0.0001);

        self::assertSame(1, $request->timeoutMs());
        self::assertSame(1, $request->connectTimeoutMs());
    }

    public function testTimeoutRejectsNonFiniteDurations(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HttpRequest::get('https://example.test')->timeout(INF);
    }
}
