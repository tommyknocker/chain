<?php

declare(strict_types=1);

namespace tommyknocker\chain;

interface BatchResult
{
    public function key(): int|string;

    public function url(): string;

    public function status(): int;

    /** @return array<string, list<string>> */
    public function headers(): array;

    public function duration(): float;
}
