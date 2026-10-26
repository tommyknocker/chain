<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class FlowEvent
{
    public function __construct(
        public FlowEventType $type,
        public int $stepIndex,
        public string $stepName,
        public float $duration,
        public ?\Throwable $error = null,
    ) {
    }
}
