<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Chain;

final class DebugTest extends TestCase
{
    public function testDebug(): void
    {
        $this->assertTrue(class_exists('tommyknocker\chain\Chain'));
        $this->assertTrue(class_exists(Chain::class));

        $obj = new \stdClass();
        $chain = Chain::of($obj);
        $this->assertInstanceOf(Chain::class, $chain);
    }
}
