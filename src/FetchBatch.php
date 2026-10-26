<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use InvalidArgumentException;
use tommyknocker\chain\Internal\BatchSink;
use tommyknocker\chain\Internal\MemorySink;

final class FetchBatch extends AbstractBatch
{
    private int $maxBodyBytes = 16_777_216;

    public function maxBodySize(int $bytes): self
    {
        if ($bytes < 1) {
            throw new InvalidArgumentException('Maximum body size must be at least 1 byte.');
        }

        $this->maxBodyBytes = $bytes;

        return $this;
    }

    protected function createSink(int|string $key, string $url): BatchSink
    {
        return new MemorySink($this->maxBodyBytes);
    }
}
