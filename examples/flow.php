<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use tommyknocker\chain\Flow;
use tommyknocker\chain\FlowContext;
use tommyknocker\chain\StepInterface;

final class NormalizeName implements StepInterface
{
    public function handle(mixed $payload, FlowContext $context): mixed
    {
        $payload['name'] = trim((string) $payload['name']);

        return $payload;
    }
}

$flow = Flow::of(
    new NormalizeName(),
    static fn (array $payload): array => [...$payload, 'valid' => $payload['name'] !== ''],
);

echo json_encode($flow->run(['name' => ' Alice ']), JSON_THROW_ON_ERROR) . PHP_EOL;
