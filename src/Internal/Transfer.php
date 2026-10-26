<?php

declare(strict_types=1);

namespace tommyknocker\chain\Internal;

use CurlHandle;
use tommyknocker\chain\HttpRequest;

final class Transfer
{
    /** @var array<string, list<string>> */
    private array $headers = [];
    private CurlHandle $handle;

    /** @param list<string> $headers */
    public function __construct(
        private readonly int|string $key,
        private readonly HttpRequest $request,
        private readonly BatchSink $sink,
        int $timeoutMs,
        int $connectTimeoutMs,
        array $headers,
        bool $followRedirects,
        ?callable $progress = null,
    ) {
        $handle = curl_init($request->url());
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Could not initialize cURL for %s.', $request->url()));
        }

        $this->handle = $handle;
        $options = [
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT_MS => $connectTimeoutMs,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CUSTOMREQUEST => $request->method() === '' ? null : $request->method(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_WRITEFUNCTION => function (CurlHandle $handle, string $chunk): int {
                return $this->sink->write($chunk);
            },
            CURLOPT_HEADERFUNCTION => function (CurlHandle $handle, string $header): int {
                $this->captureHeader($header);

                return strlen($header);
            },
        ];
        if ($request->bodyValue() !== null) {
            $options[CURLOPT_POSTFIELDS] = $request->bodyValue();
        }
        if ($request->basicCredentials() !== null) {
            [$username, $password] = $request->basicCredentials();
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_USERPWD] = $username . ':' . $password;
        }
        if ($progress !== null) {
            $options[CURLOPT_NOPROGRESS] = false;
            $options[CURLOPT_XFERINFOFUNCTION] = function (CurlHandle $handle, int $downloadTotal, int $downloaded) use ($progress): int {
                $progress($downloaded, $downloadTotal > 0 ? $downloadTotal : null);

                return 0;
            };
        }
        curl_setopt_array($this->handle, $options);
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
        return $this->request->url();
    }

    public function request(): HttpRequest
    {
        return $this->request;
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
