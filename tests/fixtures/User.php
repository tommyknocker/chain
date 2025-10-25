<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class User
{
    private bool $isAdmin = false;
    private ?string $email = null;
    private bool $isVerified = false;
    private bool $isPremium = false;
    private array $roles = ['user'];

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

    public function verify(): self
    {
        $this->isVerified = true;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function upgradeToPremium(): self
    {
        $this->isPremium = true;

        return $this;
    }

    public function isPremium(): bool
    {
        return $this->isPremium;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
