<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class Profile
{
    private string $bio = '';
    private string $avatarUrl = '/img/default-avatar.png';
    private array $preferences = [];
    private array $notifications = [];
    private int $completeness = 0;

    public function __construct(private User $user)
    {
        $this->preferences = [
            'theme' => 'light',
            'language' => 'en',
            'newsletter' => false,
        ];
        $this->calculateCompleteness();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setBio(string $bio): self
    {
        $this->bio = $bio;
        $this->calculateCompleteness();

        return $this;
    }

    public function getBio(): string
    {
        return $this->bio;
    }

    public function setAvatar(string $url): self
    {
        $this->avatarUrl = $url;
        $this->calculateCompleteness();

        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatarUrl;
    }

    public function setPreference(string $key, mixed $value): self
    {
        $this->preferences[$key] = $value;

        return $this;
    }

    public function getPreference(string $key): mixed
    {
        return $this->preferences[$key] ?? null;
    }

    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function enablePremiumFeatures(): self
    {
        $this->setPreference('theme', 'premium-dark');
        $this->setPreference('storage_limit', '100GB');
        $this->setPreference('priority_support', true);

        return $this;
    }

    public function addNotification(string $message): self
    {
        $this->notifications[] = [
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $this;
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    public function getCompleteness(): int
    {
        return $this->completeness;
    }

    private function calculateCompleteness(): void
    {
        $score = 20; // base score
        if ($this->user->getEmail()) {
            $score += 20;
        }
        if ($this->bio !== '') {
            $score += 20;
        }
        if ($this->avatarUrl !== '/img/default-avatar.png') {
            $score += 20;
        }
        if ($this->user->isVerified()) {
            $score += 20;
        }

        $this->completeness = min($score, 100);
    }

    public function toArray(): array
    {
        return [
            'user' => [
                'name' => $this->user->getName(),
                'email' => $this->user->getEmail(),
                'age' => $this->user->getAge(),
                'verified' => $this->user->isVerified(),
                'premium' => $this->user->isPremium(),
                'roles' => $this->user->getRoles(),
            ],
            'profile' => [
                'bio' => $this->bio,
                'avatar' => $this->avatarUrl,
                'preferences' => $this->preferences,
                'completeness' => $this->completeness . '%',
                'notifications' => count($this->notifications) . ' unread',
            ],
        ];
    }
}
