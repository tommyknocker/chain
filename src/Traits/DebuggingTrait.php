<?php

declare(strict_types=1);

namespace tommyknocker\chain\Traits;

/**
 * Trait providing debugging functionality.
 */
trait DebuggingTrait
{
    /**
     * Debug helper - dump current value and continue chain.
     *
     * @param string $label Optional label for the dump
     */
    public function dump(string $label = ''): self
    {
        $value = $this->result ?? $this->instance;
        $output = print_r($value, true);
        echo ($label !== '' ? "[$label] " : '') . $output . "\n";

        return $this;
    }

    /**
     * Debug helper - dump current value and die.
     *
     * @param string $label Optional label for the dump
     * @return never
     */
    public function dd(string $label = ''): void
    {
        $this->dump($label);
        exit(1);
    }
}
