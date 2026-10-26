<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use Closure;
use InvalidArgumentException;
use Throwable;

final readonly class RetryPolicy
{
    /** @var null|Closure(Throwable, RetryAttempt): bool */
    public ?Closure $retryIf;

    /**
     * @param null|Closure(Throwable, RetryAttempt): bool $retryIf
     */
    public function __construct(
        public int $maxAttempts = 3,
        public int $initialDelayMs = 0,
        public float $backoffMultiplier = 1.0,
        public ?int $maxDelayMs = null,
        public int $jitterMs = 0,
        ?Closure $retryIf = null
    ) {
        if ($maxAttempts < 1 || $initialDelayMs < 0 || $backoffMultiplier < 1 || $jitterMs < 0) {
            throw new InvalidArgumentException('Invalid retry policy.');
        }
        if ($maxDelayMs !== null && $maxDelayMs < 0) {
            throw new InvalidArgumentException('Retry max delay must be zero or greater.');
        }

        $this->retryIf = $retryIf;
    }

    public function shouldRetry(Throwable $error, RetryAttempt $attempt): bool
    {
        return $this->retryIf === null || ($this->retryIf)($error, $attempt);
    }

    public function delayAfter(int $failedAttempt): int
    {
        $delay = (int) round($this->initialDelayMs * ($this->backoffMultiplier ** ($failedAttempt - 1)));
        $delay = $this->maxDelayMs === null ? $delay : min($delay, $this->maxDelayMs);

        if ($this->jitterMs > 0) {
            $delay += random_int(-$this->jitterMs, $this->jitterMs);
        }

        return max(0, $delay);
    }
}
