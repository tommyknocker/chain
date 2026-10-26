<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use Throwable;

/**
 * Optional extension capability for observing failed wrapped-object calls.
 */
interface ChainErrorExtensionInterface
{
    /**
     * Called when a before hook, wrapped method, or after hook throws.
     * Errors thrown by this observer are ignored so the original error is preserved.
     *
     * @param string $method The wrapped-object method name
     * @param array<int|string, mixed> $args The arguments passed to the wrapped-object method
     */
    public function onMethodError(string $method, array $args, Throwable $error): void;
}
