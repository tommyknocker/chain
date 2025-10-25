<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class Calculator
{
    public function __construct(private float $value = 0)
    {
    }

    public function add(float $n): self
    {
        $this->value += $n;

        return $this;
    }

    public function subtract(float $n): self
    {
        $this->value -= $n;

        return $this;
    }

    public function multiply(float $n): self
    {
        $this->value *= $n;

        return $this;
    }

    public function divide(float $n): self
    {
        if ($n === 0.0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }
        $this->value /= $n;

        return $this;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    public function isNegative(): bool
    {
        return $this->value < 0;
    }
}
