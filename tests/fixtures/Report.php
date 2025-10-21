<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class Report
{
    public function __construct(
        private float $total,
        private int $count
    ) {
    }

    public function format(): string
    {
        $avg = $this->count > 0 ? $this->total / $this->count : 0;
        return sprintf("Total: %.2f, Count: %d, Average: %.2f", $this->total, $this->count, $avg);
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getAverage(): float
    {
        return $this->count > 0 ? $this->total / $this->count : 0;
    }
}

