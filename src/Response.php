<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use JsonException;

final readonly class Response implements BatchResult
{
    /** @param array<string, list<string>> $headers */
    public function __construct(
        private int|string $key,
        private string $url,
        private int $status,
        private array $headers,
        private float $duration,
        private string $body,
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

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @throws JsonException
     */
    public function json(bool $associative = true): mixed
    {
        return json_decode($this->body, $associative, 512, JSON_THROW_ON_ERROR);
    }
}
