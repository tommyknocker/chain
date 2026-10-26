<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class Failure
{
    public function __construct(
        private int|string $key,
        private string $url,
        private FailureType $type,
        private string $message,
        private ?int $curlCode = null,
        private ?int $status = null,
        private int $attempts = 1,
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

    public function type(): FailureType
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

    public function attempts(): int
    {
        return $this->attempts;
    }
}
