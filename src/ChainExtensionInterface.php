<?php

declare(strict_types=1);

namespace tommyknocker\chain;

/**
 * Interface for extending Chain functionality.
 */
interface ChainExtensionInterface
{
    /**
     * Called before a method is invoked on the wrapped object.
     *
     * @param string $method The method name being called
     * @param array<int, mixed> $args The arguments being passed to the method
     */
    public function beforeMethodCall(string $method, array $args): void;

    /**
     * Called after a method has been invoked on the wrapped object.
     *
     * @param string $method The method name that was called
     * @param mixed $result The result returned by the method
     */
    public function afterMethodCall(string $method, mixed $result): void;
}
