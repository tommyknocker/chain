<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class Counter
{
    public function __construct(private int $value = 0)
    {
    }

    public function inc(int $n = 1): self
    {
        $this->value += $n;

        return $this;
    }

    public function dec(int $n = 1): self
    {
        $this->value -= $n;

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function reset(): self
    {
        $this->value = 0;

        return $this;
    }
}
