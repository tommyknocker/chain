<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class Logger
{
    private array $logs = [];

    public function log(string $message): self
    {
        $this->logs[] = $message;

        return $this;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function clear(): self
    {
        $this->logs = [];

        return $this;
    }

    public function count(): int
    {
        return count($this->logs);
    }
}
