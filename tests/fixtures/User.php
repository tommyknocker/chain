<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class User
{
    private bool $isAdmin = false;
    private ?string $email = null;
    public function __construct(private string $name, private int $age)
    {
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getAge(): int
    {
        return $this->age;
    }
    public function setAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function isAdult(): bool
    {
        return $this->age >= 18;
    }
}
