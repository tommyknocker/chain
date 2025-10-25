<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class DataProcessor
{
    public function __construct(private array $data = [])
    {
    }

    public function addItem(mixed $item): self
    {
        $this->data[] = $item;

        return $this;
    }

    public function filter(callable $fn): self
    {
        $this->data = array_filter($this->data, $fn);

        return $this;
    }

    public function transform(callable $fn): self
    {
        $this->data = array_map($fn, $this->data);

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function sum(): float
    {
        return array_sum($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }
}
