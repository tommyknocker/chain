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
     * @param callable ...$conditions Conditions to check
     */
    public function whenAll(callable ...$conditions): self
    {
        $allTrue = true;
        foreach ($conditions as $condition) {
            if (!($condition($this->instance))) {
                $allTrue = false;
                break;
            }
        }

        return $this->when($allTrue, fn ($chain) => $chain);
    }

    /**
     * Execute callback when any condition is true.
     *
     * @param callable ...$conditions Conditions to check
     */
    public function whenAny(callable ...$conditions): self
    {
        $anyTrue = false;
        foreach ($conditions as $condition) {
            if ($condition($this->instance)) {
                $anyTrue = true;
                break;
            }
        }

        return $this->when($anyTrue, fn ($chain) => $chain);
    }

    /**
     * Execute callback when none of the conditions are true.
     *
     * @param callable ...$conditions Conditions to check
     */
    public function whenNone(callable ...$conditions): self
    {
        $noneTrue = true;
        foreach ($conditions as $condition) {
            if ($condition($this->instance)) {
                $noneTrue = false;
                break;
            }
        }

        return $this->when($noneTrue, fn ($chain) => $chain);
    }
}
