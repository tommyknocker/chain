<?php

declare(strict_types=1);

namespace tommyknocker\chain;

final class Report
{
    /** @var array<int|string, BatchResult> */
    private array $successes = [];
    /** @var array<int|string, Failure> */
    private array $failures = [];

    public function addSuccess(BatchResult $result): void
    {
        $this->successes[$result->key()] = $result;
        unset($this->failures[$result->key()]);
    }

    public function addFailure(Failure $failure): void
    {
        $this->failures[$failure->key()] = $failure;
    }

    public function allSucceeded(): bool
    {
        return $this->failures === [];
    }

    public function hasFailures(): bool
    {
        return $this->failures !== [];
    }

    /** @return array<int|string, string> */
    public function bodies(): array
    {
        $bodies = [];
        foreach ($this->responses() as $key => $response) {
            $bodies[$key] = $response->body();
        }

        return $bodies;
    }

    /** @return array<int|string, Response> */
    public function responses(): array
    {
        return array_filter($this->successes, static fn (BatchResult $result): bool => $result instanceof Response);
    }

    /** @return array<int|string, Download> */
    public function downloads(): array
    {
        return array_filter($this->successes, static fn (BatchResult $result): bool => $result instanceof Download);
    }

    public function response(int|string $key): ?Response
    {
        $result = $this->successes[$key] ?? null;

        return $result instanceof Response ? $result : null;
    }

    public function download(int|string $key): ?Download
    {
        $result = $this->successes[$key] ?? null;

        return $result instanceof Download ? $result : null;
    }

    public function failure(int|string $key): ?Failure
    {
        return $this->failures[$key] ?? null;
    }

    /** @return array<int|string, Failure> */
    public function failures(): array
    {
        return $this->failures;
    }

    /** @throws BatchFailedException */
    public function throwIfFailed(): self
    {
        if ($this->hasFailures()) {
            throw new BatchFailedException($this);
        }

        return $this;
    }
}
