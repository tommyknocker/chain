<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

use Psr\Container\ContainerInterface;

final class SimpleContainer implements ContainerInterface
{
    private array $services = [];

    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new \RuntimeException("Service $id not found");
        }
        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}

