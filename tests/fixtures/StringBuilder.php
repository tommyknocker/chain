<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class StringBuilder
{
    private string $text;

    public function __construct(string $initial = '')
    {
        $this->text = $initial;
    }

    public function append(string $str): self
    {
        $this->text .= $str;

        return $this;
    }

    public function prepend(string $str): self
    {
        $this->text = $str . $this->text;

        return $this;
    }

    public function uppercase(): self
    {
        $this->text = strtoupper($this->text);

        return $this;
    }

    public function lowercase(): self
    {
        $this->text = strtolower($this->text);

        return $this;
    }

    public function toString(): string
    {
        return $this->text;
    }
}
