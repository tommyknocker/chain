<?php

declare(strict_types=1);

namespace tommyknocker\chain\Internal;

use CurlHandle;

final class Transfer
{
    /** @var array<string, list<string>> */
    private array $headers = [];
    private CurlHandle $handle;

    public function __construct(
        private readonly int|string $key,
        private readonly string $url,
        private readonly BatchSink $sink,
        int $timeoutMs,
        int $connectTimeoutMs,
    ) {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Could not initialize cURL for %s.', $url));
        }

        $this->handle = $handle;
        curl_setopt_array($this->handle, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT_MS => $connectTimeoutMs,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function (CurlHandle $handle, string $chunk): int {
                return $this->sink->write($chunk);
            },
            CURLOPT_HEADERFUNCTION => function (CurlHandle $handle, string $header): int {
                $this->captureHeader($header);

                return strlen($header);
            },
        ]);
    }

    public function handle(): CurlHandle
    {
        return $this->handle;
    }

    public function key(): int|string
    {
        return $this->key;
    }

    public function url(): string
    {
        return $this->url;
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function sink(): BatchSink
    {
        return $this->sink;
    }

    private function captureHeader(string $header): void
    {
        if (str_starts_with($header, 'HTTP/')) {
            $this->headers = [];

            return;
        }

        $header = trim($header);
        if ($header === '' || !str_contains($header, ':')) {
            return;
        }

        [$name, $value] = explode(':', $header, 2);
        $this->headers[strtolower(trim($name))][] = trim($value);
    }
}
