<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class Order
{
    public function __construct(private float $total)
    {
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function addTax(float $rate = 0.1): self
    {
        $this->total *= (1 + $rate);
        return $this;
    }

    public function applyDiscount(float $amount): self
    {
        $this->total -= $amount;
        return $this;
    }
}

