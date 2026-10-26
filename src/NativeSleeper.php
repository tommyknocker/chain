<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final class NativeSleeper implements SleeperInterface
{
    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
