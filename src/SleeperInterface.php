<?php

declare(strict_types=1);

namespace tommyknocker\chain;

interface SleeperInterface
{
    public function sleep(int $milliseconds): void;
}
