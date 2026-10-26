<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\ChainConfig;

final class BenchmarkCounter
{
    public function increment(): self
    {
        return $this;
    }
}

/** @return float elapsed milliseconds */
function benchmark(bool $methodCaching, int $iterations): float
{
    $chain = Chain::of(new BenchmarkCounter())
        ->withConfig(new ChainConfig(enableMethodCaching: $methodCaching));
    $start = hrtime(true);

    for ($index = 0; $index < $iterations; $index++) {
        $chain->increment();
    }

    return (hrtime(true) - $start) / 1_000_000;
}

$iterations = 100_000;
$cached = benchmark(true, $iterations);
$uncached = benchmark(false, $iterations);

printf("%d calls\n", $iterations);
printf("method cache enabled: %.2f ms\n", $cached);
printf("method cache disabled: %.2f ms\n", $uncached);
printf("speedup: %.2fx\n", $uncached / $cached);
