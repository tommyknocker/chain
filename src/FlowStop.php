<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class FlowStop
{
    public function __construct(public mixed $value)
    {
    }
}
