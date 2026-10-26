<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use InvalidArgumentException;
use JsonException;

final class HttpRequest
{
    /** @var array<string, array{name: string, values: list<string>}> */
    private array $headers = [];
    /** @var list<int>|null */
    private ?array $acceptedStatuses = null;
    /** @var list<string>|null */
    private ?array $expectedContentTypes = null;
    private ?string $body = null;
    /** @var array{string, string}|null */
    private ?array $basicAuth = null;
    private ?int $timeoutMs = null;
    private ?int $connectTimeoutMs = null;
    private ?int $maxBodyBytes = null;

    private function __construct(private string $method, private string $url)
    {
    }

    public static function get(string $url): self
    {
        return new self('GET', $url);
    }

    public static function post(string $url, string $body = ''): self
    {
        return new self('POST', $url)->body($body);
    }

    public static function put(string $url, string $body = ''): self
    {
        return new self('PUT', $url)->body($body);
    }

    public static function patch(string $url, string $body = ''): self
    {
        return new self('PATCH', $url)->body($body);
    }

    public static function delete(string $url): self
    {
        return new self('DELETE', $url);
    }

    public function header(string $name, string $value): self
    {
        if ($name === '' || str_contains($name, ':') || str_contains($name, "\r") || str_contains($name, "\n")) {
            throw new InvalidArgumentException('HTTP header name is invalid.');
        }
        if (str_contains($value, "\r") || str_contains($value, "\n")) {
            throw new InvalidArgumentException('HTTP header value must not contain newlines.');
        }

        $clone = clone $this;
        $clone->headers[strtolower($name)] = ['name' => $name, 'values' => [$value]];

        return $clone;
    }

    public function withoutHeader(string $name): self
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);

        return $clone;
    }

    /** @param array<string, string|list<string>> $headers */
    public function headers(array $headers): self
    {
        $request = $this;
        foreach ($headers as $name => $values) {
            $values = is_array($values) ? $values : [$values];
            if ($values === []) {
                throw new InvalidArgumentException('HTTP header values cannot be empty.');
            }
            $request = $request->header($name, $values[0]);
            foreach ($values as $value) {
                if (str_contains($value, "\r") || str_contains($value, "\n")) {
                    throw new InvalidArgumentException('HTTP header value must not contain newlines.');
                }
            }
            $key = strtolower($name);
            $request->headers[$key]['values'] = $values;
        }

        return $request;
    }

    /** @param array<string, scalar|list<scalar>|null> $parameters */
    public function query(array $parameters): self
    {
        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        if ($query === '') {
            return $this;
        }

        $clone = clone $this;
        $clone->url .= str_contains($clone->url, '?') ? '&' . $query : '?' . $query;

        return $clone;
    }

    public function body(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /** @throws JsonException */
    public function json(mixed $payload): self
    {
        return $this->body(json_encode($payload, JSON_THROW_ON_ERROR))->header('Content-Type', 'application/json');
    }

    public function bearerToken(string $token): self
    {
        return $this->header('Authorization', 'Bearer ' . $token);
    }

    public function basicAuth(string $username, string $password): self
    {
        $clone = clone $this;
        $clone->basicAuth = [$username, $password];

        return $clone;
    }

    public function timeout(float $seconds): self
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than zero.');
        }

        $clone = clone $this;
        $clone->timeoutMs = (int) round($seconds * 1000);

        return $clone;
    }

    public function connectTimeout(float $seconds): self
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Connect timeout must be greater than zero.');
        }

        $clone = clone $this;
        $clone->connectTimeoutMs = (int) round($seconds * 1000);

        return $clone;
    }

    public function expectStatuses(int ...$statuses): self
    {
        if ($statuses === []) {
            throw new InvalidArgumentException('At least one expected HTTP status is required.');
        }

        foreach ($statuses as $status) {
            if ($status < 100 || $status > 599) {
                throw new InvalidArgumentException('Expected HTTP statuses must be between 100 and 599.');
            }
        }

        $clone = clone $this;
        $clone->acceptedStatuses = array_values(array_unique($statuses));

        return $clone;
    }

    public function expectContentType(string ...$types): self
    {
        if ($types === []) {
            throw new InvalidArgumentException('At least one expected content type is required.');
        }

        $clone = clone $this;
        $clone->expectedContentTypes = array_values($types);

        return $clone;
    }

    public function expectJson(): self
    {
        return $this->expectContentType('application/json', 'application/*+json');
    }

    public function maxBodySize(int $bytes): self
    {
        if ($bytes < 1) {
            throw new InvalidArgumentException('Maximum body size must be at least 1 byte.');
        }

        $clone = clone $this;
        $clone->maxBodyBytes = $bytes;

        return $clone;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function bodyValue(): ?string
    {
        return $this->body;
    }

    /** @return array<string, array{name: string, values: list<string>}> */
    public function headerValues(): array
    {
        return $this->headers;
    }

    /** @return list<int>|null */
    public function acceptedStatuses(): ?array
    {
        return $this->acceptedStatuses;
    }

    /** @return list<string>|null */
    public function expectedContentTypes(): ?array
    {
        return $this->expectedContentTypes;
    }

    /** @return array{string, string}|null */
    public function basicCredentials(): ?array
    {
        return $this->basicAuth;
    }

    public function timeoutMs(): ?int
    {
        return $this->timeoutMs;
    }

    public function connectTimeoutMs(): ?int
    {
        return $this->connectTimeoutMs;
    }

    public function maxBodyBytes(): ?int
    {
        return $this->maxBodyBytes;
    }
}
