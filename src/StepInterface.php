<?php

declare(strict_types=1);

namespace tommyknocker\chain;

interface StepInterface
{
    public function handle(mixed $payload, FlowContext $context): mixed;
}
