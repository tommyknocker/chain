<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class Failure
{
    public function __construct(
        private int|string $key,
        private string $url,
        private string $type,
        private string $message,
        private ?int $curlCode = null,
        private ?int $status = null,
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

    public function type(): string
    {
        return $this->type;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function curlCode(): ?int
    {
        return $this->curlCode;
    }

    public function status(): ?int
    {
        return $this->status;
    }
}
