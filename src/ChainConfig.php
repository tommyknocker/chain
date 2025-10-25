<?php

declare(strict_types=1);

namespace tommyknocker\chain;

/**
 * Configuration class for Chain behavior customization.
 */
final class ChainConfig
{
    public function __construct(
        public bool $enableMethodCaching = true,
        public bool $enableDeepCloning = false,
        public int $maxRetryAttempts = 3,
        public int $defaultRetryDelay = 100,
        public int $defaultTimeout = 30
    ) {
    }

    /**
     * Create a default configuration.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create a performance-optimized configuration.
     */
    public static function performance(): self
    {
        return new self(
            enableMethodCaching: true,
            enableDeepCloning: false,
            maxRetryAttempts: 5,
            defaultRetryDelay: 50,
            defaultTimeout: 60
        );
    }

    /**
     * Create a development configuration with debugging enabled.
     */
    public static function development(): self
    {
        return new self(
            enableMethodCaching: false,
            enableDeepCloning: true,
            maxRetryAttempts: 1,
            defaultRetryDelay: 0,
            defaultTimeout: 10
        );
    }
}
