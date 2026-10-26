<?php

declare(strict_types=1);

namespace tommyknocker\chain\Traits;

/**
 * Trait providing enhanced conditional execution functionality.
 */
trait ConditionalTrait
{
    /**
     * Execute callback when all conditions are true.
     *
     * @param array<callable> $conditions Conditions to check
     */
    public function whenAll(array $conditions, callable $callback, ?callable $default = null): self
    {
        foreach ($conditions as $condition) {
            if (!$condition($this->instance)) {
                return $this->when(false, $callback, $default);
            }
        }

        return $this->when(true, $callback, $default);
    }

    /**
     * Execute callback when any condition is true.
     *
     * @param array<callable> $conditions Conditions to check
     */
    public function whenAny(array $conditions, callable $callback, ?callable $default = null): self
    {
        foreach ($conditions as $condition) {
            if ($condition($this->instance)) {
                return $this->when(true, $callback, $default);
            }
        }

        return $this->when(false, $callback, $default);
    }

    /**
     * Execute callback when none of the conditions are true.
     *
     * @param array<callable> $conditions Conditions to check
     */
    public function whenNone(array $conditions, callable $callback, ?callable $default = null): self
    {
        foreach ($conditions as $condition) {
            if ($condition($this->instance)) {
                return $this->when(false, $callback, $default);
            }
        }

        return $this->when(true, $callback, $default);
    }
}
