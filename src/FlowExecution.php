<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final readonly class FlowExecution
{
    /** @param list<FlowStepResult> $steps */
    public function __construct(
        private mixed $result,
        private ?FlowFailedException $failure,
        private bool $stopped,
        private array $steps,
    ) {
    }

    public function succeeded(): bool
    {
        return $this->failure === null;
    }

    public function failed(): bool
    {
        return $this->failure !== null;
    }

    public function stopped(): bool
    {
        return $this->stopped;
    }

    public function result(): mixed
    {
        return $this->result;
    }

    public function failure(): ?FlowFailedException
    {
        return $this->failure;
    }

    /** @return list<FlowStepResult> */
    public function steps(): array
    {
        return $this->steps;
    }

    public function unwrap(): mixed
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->result;
    }
}
