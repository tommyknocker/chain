<?php

declare(strict_types=1);

namespace tommyknocker\chain\Internal;

use tommyknocker\chain\BatchResult;

interface BatchSink
{
    /** Return the number of bytes consumed, as required by CURLOPT_WRITEFUNCTION. */
    public function write(string $chunk): int;

    /** @param array<string, list<string>> $headers */
    public function finish(int|string $key, string $url, int $status, array $headers, float $duration): BatchResult;

    public function abort(): void;

    public function failureMessage(): ?string;
}
