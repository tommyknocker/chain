<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class FlowStepResult
{
    public function __construct(public int $index, public string $name, public float $duration)
    {
    }
}
