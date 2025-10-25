<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class Account
{
    public function __construct(private float $balance = 0)
    {
    }

    public function deposit(float $amount): self
    {
        $this->balance += $amount;

        return $this;
    }

    public function withdraw(float $amount): self
    {
        $this->balance -= $amount;

        return $this;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function addBonus(): self
    {
        $this->balance += 100;

        return $this;
    }

    public function applyInterest(float $rate = 0.05): self
    {
        $this->balance *= (1 + $rate);

        return $this;
    }
}
