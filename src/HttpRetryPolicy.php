<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use InvalidArgumentException;

final readonly class HttpRetryPolicy
{
    /** @param list<int> $retryStatuses */
    public function __construct(
        public int $maxAttempts = 3,
        public float $initialDelaySeconds = 0.2,
        public float $backoffMultiplier = 2.0,
        public float $maxDelaySeconds = 3.0,
        public array $retryStatuses = [408, 429, 502, 503, 504],
        public bool $retryUnsafeMethods = false,
    ) {
        if ($maxAttempts < 1 || $initialDelaySeconds < 0 || $backoffMultiplier < 1 || $maxDelaySeconds < 0) {
            throw new InvalidArgumentException('Retry policy values are invalid.');
        }
    }

    public static function transient(int $maxAttempts = 3): self
    {
        return new self(maxAttempts: $maxAttempts);
    }

    public function delayForAttempt(int $attempt): float
    {
        return min($this->maxDelaySeconds, $this->initialDelaySeconds * ($this->backoffMultiplier ** max(0, $attempt - 1)));
    }

    public function allowsMethod(string $method): bool
    {
        return $this->retryUnsafeMethods || in_array($method, ['GET', 'HEAD'], true);
    }

    public function shouldRetry(Failure $failure, HttpRequest $request, int $attempt): bool
    {
        if ($attempt >= $this->maxAttempts || !$this->allowsMethod($request->method())) {
            return false;
        }

        return match ($failure->type()) {
            FailureType::Transport, FailureType::Timeout => true,
            FailureType::HttpStatus => $failure->status() !== null && in_array($failure->status(), $this->retryStatuses, true),
            default => false,
        };
    }
}
