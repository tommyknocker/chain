<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class BatchEvent
{
    public function __construct(
        public BatchEventType $type,
        public int|string $key,
        public string $url,
        public int $attempt,
        public ?BatchResult $result = null,
        public ?Failure $failure = null,
        public ?int $downloadedBytes = null,
        public ?int $totalBytes = null,
    ) {
    }
}
