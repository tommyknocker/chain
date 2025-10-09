<?php

namespace tommyknocker\chain\tests\fixtures;

final class DummyClass
{
    public function __construct(private string $value) {}
    public function getValue(): string { return $this->value; }
}
