<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use Psr\Container\ContainerInterface;

final readonly class FlowContext
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private array $attributes = [], private ?ContainerInterface $resolver = null)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function resolver(): ?ContainerInterface
    {
        return $this->resolver;
    }
}
