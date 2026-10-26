<?php

declare(strict_types=1);

namespace tommyknocker\chain\Internal;

use tommyknocker\chain\BatchResult;
use tommyknocker\chain\Response;

final class MemorySink implements BatchSink
{
    private string $body = '';
    private ?string $failureMessage = null;

    public function __construct(private readonly int $maxBytes)
    {
    }

    public function write(string $chunk): int
    {
        if (strlen($this->body) + strlen($chunk) > $this->maxBytes) {
            $this->failureMessage = sprintf('Response body exceeds the %d byte limit.', $this->maxBytes);

            return 0;
        }

        $this->body .= $chunk;

        return strlen($chunk);
    }

    /** @param array<string, list<string>> $headers */
    public function finish(int|string $key, string $url, int $status, array $headers, float $duration): BatchResult
    {
        return new Response($key, $url, $status, $headers, $duration, $this->body);
    }

    public function abort(): void
    {
        $this->body = '';
    }

    public function failureMessage(): ?string
    {
        return $this->failureMessage;
    }
}
