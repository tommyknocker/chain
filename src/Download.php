<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class Download implements BatchResult
{
    /** @param array<string, list<string>> $headers */
    public function __construct(
        private int|string $key,
        private string $url,
        private int $status,
        private array $headers,
        private float $duration,
        private string $path,
        private int $bytes,
    ) {
    }

    public function key(): int|string
    {
        return $this->key;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function duration(): float
    {
        return $this->duration;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function bytes(): int
    {
        return $this->bytes;
    }
}
