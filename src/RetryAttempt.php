<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use Throwable;

final readonly class RetryAttempt
{
    public function __construct(
        public int $number,
        public int $maxAttempts,
        public ?Throwable $previousError = null
    ) {
    }
}
