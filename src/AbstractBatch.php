<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use InvalidArgumentException;
use RuntimeException;
use tommyknocker\chain\Internal\BatchSink;
use tommyknocker\chain\Internal\Transfer;

abstract class AbstractBatch
{
    protected int $concurrency = 8;
    protected int $timeoutMs = 30_000;
    protected int $connectTimeoutMs = 10_000;
    /** @var array<int, true>|null */
    private ?array $acceptedStatuses = null;
    /** @var list<string>|null */
    private ?array $expectedContentTypes = null;
    /** @var array<string, array{name: string, values: list<string>}> */
    private array $headers = [];
    /** @var array{string, string}|null */
    private ?array $basicAuth = null;
    private ?bool $followRedirects = null;
    private ?HttpRetryPolicy $retryPolicy = null;
    private ?int $maxJobs = null;
    private bool $failFast = false;
    private bool $cancelled = false;
    /** @var list<callable(BatchEvent): void> */
    private array $observers = [];

    /** @param iterable<int|string, string|HttpRequest> $requests */
    public function __construct(private readonly iterable $requests)
    {
    }

    public function concurrency(int $limit): static
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Concurrency must be at least 1.');
        } $this->concurrency = $limit;

        return $this;
    }

    public function timeout(float $seconds): static
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than zero.');
        } $this->timeoutMs = (int) round($seconds * 1000);

        return $this;
    }

    public function connectTimeout(float $seconds): static
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Connect timeout must be greater than zero.');
        } $this->connectTimeoutMs = (int) round($seconds * 1000);

        return $this;
    }

    public function followRedirects(bool $enabled = true): static
    {
        $this->followRedirects = $enabled;

        return $this;
    }

    public function retry(HttpRetryPolicy $policy): static
    {
        $this->retryPolicy = $policy;

        return $this;
    }

    public function maxJobs(int $limit): static
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Maximum job count must be at least 1.');
        }

        $this->maxJobs = $limit;

        return $this;
    }

    public function failFast(): static
    {
        $this->failFast = true;

        return $this;
    }

    public function cancel(): void
    {
        $this->cancelled = true;
    }

    public function header(string $name, string $value): static
    {
        foreach (HttpRequest::get('https://headers.invalid')->header($name, $value)->headerValues() as $key => $header) {
            $this->headers[$key] = $header;
        }

        return $this;
    }

    /** @param array<string, string|list<string>> $headers */
    public function headers(array $headers): static
    {
        foreach (HttpRequest::get('https://headers.invalid')->headers($headers)->headerValues() as $key => $header) {
            $this->headers[$key] = $header;
        }

        return $this;
    }

    public function withoutHeader(string $name): static
    {
        unset($this->headers[strtolower($name)]);

        return $this;
    }

    public function bearerToken(string $token): static
    {
        return $this->header('Authorization', 'Bearer ' . $token);
    }

    public function basicAuth(string $username, string $password): static
    {
        $this->basicAuth = [$username, $password];

        return $this;
    }

    public function expectContentType(string ...$types): static
    {
        if ($types === []) {
            throw new InvalidArgumentException('At least one expected content type is required.');
        } $this->expectedContentTypes = array_values($types);

        return $this;
    }

    public function expectJson(): static
    {
        return $this->expectContentType('application/json', 'application/*+json');
    }

    public function acceptStatuses(int ...$statuses): static
    {
        if ($statuses === []) {
            throw new InvalidArgumentException('At least one accepted HTTP status is required.');
        } $this->acceptedStatuses = [];
        foreach ($statuses as $status) {
            if ($status < 100 || $status > 599) {
                throw new InvalidArgumentException('Accepted HTTP statuses must be between 100 and 599.');
            } $this->acceptedStatuses[$status] = true;
        }

        return $this;
    }

    /** @param callable(BatchEvent): void $observer */
    public function onEvent(callable $observer): static
    {
        $this->observers[] = $observer;

        return $this;
    }

    /** @param callable(BatchEvent): void $observer */
    public function onCompleted(callable $observer): static
    {
        return $this->onEvent(static function (BatchEvent $event) use ($observer): void {
            if ($event->type === BatchEventType::Completed) {
                $observer($event);
            }
        });
    }

    /** @param callable(BatchEvent): void $observer */
    public function onFailed(callable $observer): static
    {
        return $this->onEvent(static function (BatchEvent $event) use ($observer): void {
            if ($event->type === BatchEventType::Failed) {
                $observer($event);
            }
        });
    }

    /** @param callable(BatchEvent): void $observer */
    public function onAttemptFailed(callable $observer): static
    {
        return $this->onEvent(static function (BatchEvent $event) use ($observer): void {
            if ($event->type === BatchEventType::AttemptFailed) {
                $observer($event);
            }
        });
    }

    /** @param callable(BatchEvent): void $observer */
    public function onProgress(callable $observer): static
    {
        return $this->onEvent(static function (BatchEvent $event) use ($observer): void {
            if ($event->type === BatchEventType::Progress) {
                $observer($event);
            }
        });
    }

    public function run(): Report
    {
        $report = new Report();
        $pending = $this->prepareJobs($report);
        /** @var list<array{float, array{int|string, HttpRequest, int}}> $delayed */
        $delayed = [];
        $multi = curl_multi_init();
        $active = [];
        $next = 0;

        try {
            while (!$this->cancelled && ($next < count($pending) || $delayed !== [] || $active !== [])) {
                $now = microtime(true);
                foreach ($delayed as $index => [$due, $job]) {
                    if ($due <= $now) {
                        $pending[] = $job;
                        unset($delayed[$index]);
                    }
                }
                $delayed = array_values($delayed);
                while (count($active) < $this->concurrency && $next < count($pending)) {
                    [$key, $request, $attempt] = $pending[$next++];
                    $sink = null;

                    try {
                        $sink = $this->createSink($key, $request);
                        $transfer = new Transfer($key, $request, $sink, $request->timeoutMs() ?? $this->timeoutMs, $request->connectTimeoutMs() ?? $this->connectTimeoutMs, $this->headersFor($request), $this->shouldFollow($request), fn (int $downloaded, ?int $total) => $this->emit(new BatchEvent(BatchEventType::Progress, $key, $request->url(), $attempt, downloadedBytes: $downloaded, totalBytes: $total)));
                        curl_multi_add_handle($multi, $transfer->handle());
                        $active[spl_object_id($transfer->handle())] = [$transfer, $attempt];
                        $this->emit(new BatchEvent(BatchEventType::Started, $key, $request->url(), $attempt));
                    } catch (\Throwable $error) {
                        $sink?->abort();
                        $failure = new Failure($key, $request->url(), FailureType::Preparation, $error->getMessage(), attempts: $attempt);
                        $report->addFailure($failure);
                        $this->emit(new BatchEvent(BatchEventType::Failed, $key, $request->url(), $attempt, failure: $failure));
                    }
                }
                if ($active === []) {
                    continue;
                }
                do {
                    $status = curl_multi_exec($multi, $running);
                } while ($status === CURLM_CALL_MULTI_PERFORM);
                if ($status !== CURLM_OK) {
                    throw new RuntimeException(sprintf('cURL multi error: %s', curl_multi_strerror($status)));
                }
                while (($completed = curl_multi_info_read($multi)) !== false) {
                    $handle = $completed['handle'];
                    $entry = $active[spl_object_id($handle)] ?? null;
                    if ($entry === null) {
                        continue;
                    }
                    unset($active[spl_object_id($handle)]);
                    curl_multi_remove_handle($multi, $handle);
                    [$transfer, $attempt] = $entry;
                    $failure = $this->complete($report, $transfer, $completed['result'], $attempt);
                    curl_close($handle);
                    $retryPolicy = $this->retryPolicy;
                    if ($failure !== null && $retryPolicy !== null && $retryPolicy->shouldRetry($failure, $transfer->request(), $attempt)) {
                        $this->emit(new BatchEvent(BatchEventType::AttemptFailed, $transfer->key(), $transfer->url(), $attempt, failure: $failure));
                        $nextAttempt = $attempt + 1;
                        $delayed[] = [microtime(true) + $retryPolicy->delayForAttempt($attempt), [$transfer->key(), $transfer->request(), $nextAttempt]];
                        $this->emit(new BatchEvent(BatchEventType::Retrying, $transfer->key(), $transfer->url(), $nextAttempt, failure: $failure));
                    } elseif ($failure !== null && $this->failFast) {
                        $this->emit(new BatchEvent(BatchEventType::Failed, $transfer->key(), $transfer->url(), $attempt, failure: $failure));
                        $this->cancelled = true;
                    } elseif ($failure !== null) {
                        $this->emit(new BatchEvent(BatchEventType::Failed, $transfer->key(), $transfer->url(), $attempt, failure: $failure));
                    }
                }
                if ($active === [] && $delayed !== []) {
                    $earliest = min(array_column($delayed, 0));
                    usleep((int) max(1_000, min(1_000_000, ($earliest - microtime(true)) * 1_000_000)));
                } elseif ($active !== [] && curl_multi_select($multi, 1.0) === -1) {
                    usleep(1_000);
                }
            }
        } finally {
            foreach ($active as [$transfer]) {
                $transfer->sink()->abort();
                curl_multi_remove_handle($multi, $transfer->handle());
                curl_close($transfer->handle());
            } curl_multi_close($multi);
        }

        return $report;
    }

    /** @return list<array{int|string, HttpRequest, int}> */
    private function prepareJobs(Report $report): array
    {
        $jobs = [];
        $total = 0;
        $seen = [];
        foreach ($this->requests as $key => $value) {
            $total++;
            if ($this->maxJobs !== null && $total > $this->maxJobs) {
                throw new InvalidArgumentException(sprintf('Batch exceeds its %d job limit.', $this->maxJobs));
            }
            if (isset($seen[$key])) {
                throw new InvalidArgumentException(sprintf('Batch key %s is duplicated.', (string) $key));
            } $seen[$key] = true;
            $request = is_string($value) ? HttpRequest::get($value) : $value;
            if (!$this->isHttpUrl($request->url())) {
                $report->addFailure(new Failure($key, $request->url(), FailureType::Validation, 'A job URL must be a valid HTTP or HTTPS URL.'));
                continue;
            }
            $jobs[] = [$key, $this->applyDefaults($request), 1];
        }

        return $jobs;
    }

    private function applyDefaults(HttpRequest $request): HttpRequest
    {
        if ($request->basicCredentials() === null && $this->basicAuth !== null) {
            $request = $request->basicAuth(...$this->basicAuth);
        }

        return $request;
    }

    private function complete(Report $report, Transfer $transfer, int $curlCode, int $attempt): ?Failure
    {
        $status = (int) curl_getinfo($transfer->handle(), CURLINFO_RESPONSE_CODE);
        $duration = (float) curl_getinfo($transfer->handle(), CURLINFO_TOTAL_TIME);
        $sink = $transfer->sink();
        $request = $transfer->request();
        if ($sink->failureMessage() !== null) {
            return $this->failure($report, $transfer, FailureType::SizeLimit, $sink->failureMessage(), $curlCode, $status, $attempt);
        }
        if ($curlCode !== CURLE_OK) {
            return $this->failure($report, $transfer, $curlCode === CURLE_OPERATION_TIMEDOUT ? FailureType::Timeout : FailureType::Transport, curl_error($transfer->handle()) ?: (curl_strerror($curlCode) ?? 'Unknown cURL transfer error.'), $curlCode, $status, $attempt);
        }
        if (!$this->accepts($status, $request)) {
            return $this->failure($report, $transfer, FailureType::HttpStatus, sprintf('Unexpected HTTP status %d.', $status), null, $status, $attempt);
        }
        if (!$this->acceptsType($transfer->headers(), $request)) {
            return $this->failure($report, $transfer, FailureType::ContentType, 'Unexpected response Content-Type.', null, $status, $attempt);
        }

        try {
            $result = $sink->finish($transfer->key(), $transfer->url(), $status, $transfer->headers(), $duration);
            $report->addSuccess($result);
            $this->emit(new BatchEvent(BatchEventType::Completed, $transfer->key(), $transfer->url(), $attempt, result: $result));

            return null;
        } catch (\Throwable $error) {
            return $this->failure($report, $transfer, FailureType::Preparation, $error->getMessage(), null, $status, $attempt);
        }
    }

    private function failure(Report $report, Transfer $transfer, FailureType $type, string $message, ?int $curlCode, ?int $status, int $attempt): Failure
    {
        $transfer->sink()->abort();
        $failure = new Failure($transfer->key(), $transfer->url(), $type, $message, $curlCode, $status, $attempt);
        $report->addFailure($failure);

        return $failure;
    }

    /** @return list<string> */
    private function headersFor(HttpRequest $request): array
    {
        $headers = $this->headers;
        foreach ($request->headerValues() as $key => $header) {
            $headers[$key] = $header;
        } $flat = [];
        foreach ($headers as $header) {
            foreach ($header['values'] as $value) {
                $flat[] = $header['name'] . ': ' . $value;
            }
        }

        return $flat;
    }

    private function shouldFollow(HttpRequest $request): bool
    {
        if ($this->followRedirects !== null) {
            return $this->followRedirects;
        } foreach ($this->headersFor($request) as $header) {
            if (str_starts_with(strtolower($header), 'authorization:')) {
                return false;
            }
        }

        return $request->basicCredentials() === null;
    }

    private function accepts(int $status, HttpRequest $request): bool
    {
        $statuses = $request->acceptedStatuses();
        if ($statuses !== null) {
            return in_array($status, $statuses, true);
        }

        return $this->acceptedStatuses !== null ? isset($this->acceptedStatuses[$status]) : $status >= 200 && $status < 300;
    }

    /** @param array<string, list<string>> $headers */
    private function acceptsType(array $headers, HttpRequest $request): bool
    {
        $types = $request->expectedContentTypes() ?? $this->expectedContentTypes;
        if ($types === null) {
            return true;
        } $type = strtolower(trim(explode(';', $headers['content-type'][0] ?? '', 2)[0]));
        foreach ($types as $pattern) {
            $pattern = strtolower($pattern);
            if ($type === $pattern || (str_ends_with($pattern, '/*') && str_starts_with($type, substr($pattern, 0, -1))) || ($pattern === 'application/*+json' && str_ends_with($type, '+json'))) {
                return true;
            }
        }

        return false;
    }

    private function isHttpUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function emit(BatchEvent $event): void
    {
        foreach ($this->observers as $observer) {
            try {
                $observer($event);
            } catch (\Throwable) {
            }
        }
    }

    abstract protected function createSink(int|string $key, HttpRequest $request): BatchSink;
}
