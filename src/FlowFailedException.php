<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use RuntimeException;

final class FlowFailedException extends RuntimeException
{
    public function __construct(private readonly int $stepIndex, private readonly string $stepName, \Throwable $previous)
    {
        parent::__construct(sprintf('Flow failed at step %d (%s): %s', $stepIndex, $stepName, $previous->getMessage()), 0, $previous);
    }

    public function stepIndex(): int
    {
        return $this->stepIndex;
    }

    public function stepName(): string
    {
        return $this->stepName;
    }
}
