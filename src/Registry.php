<?php
declare(strict_types=1);

namespace tommyknocker\chain;

use Psr\Container\ContainerInterface;
use RuntimeException;

final class Registry implements ContainerInterface
{
    private array $instances = [];

    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new RuntimeException("No instance registered for $id");
        }
        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]);
    }
}