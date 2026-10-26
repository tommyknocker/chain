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
}
