<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use CurlMultiHandle;
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

    /** @param iterable<int|string, mixed> $urls */
    public function __construct(private readonly iterable $urls)
    {
    }

    public function concurrency(int $limit): static
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Concurrency must be at least 1.');
        }

        $this->concurrency = $limit;

        return $this;
    }

    public function timeout(float $seconds): static
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than zero.');
        }

        $this->timeoutMs = (int) round($seconds * 1000);

        return $this;
    }

    public function connectTimeout(float $seconds): static
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Connect timeout must be greater than zero.');
        }

        $this->connectTimeoutMs = (int) round($seconds * 1000);

        return $this;
    }

    public function acceptStatuses(int ...$statuses): static
    {
        if ($statuses === []) {
            throw new InvalidArgumentException('At least one accepted HTTP status is required.');
        }

        $accepted = [];
        foreach ($statuses as $status) {
            if ($status < 100 || $status > 599) {
                throw new InvalidArgumentException('Accepted HTTP statuses must be between 100 and 599.');
            }
            $accepted[$status] = true;
        }

        $this->acceptedStatuses = $accepted;

        return $this;
    }

    public function run(): Report
    {
        $report = new Report();
        $pending = $this->prepareJobs($report);
        if ($pending === []) {
            return $report;
        }

        $multi = curl_multi_init();
        /** @var array<int, Transfer> $active */
        $active = [];
        $next = 0;

        try {
            while ($next < count($pending) || $active !== []) {
                while (count($active) < $this->concurrency && $next < count($pending)) {
                    [$key, $url] = $pending[$next++];

                    try {
                        $sink = $this->createSink($key, $url);
                        $transfer = new Transfer($key, $url, $sink, $this->timeoutMs, $this->connectTimeoutMs);
                        curl_multi_add_handle($multi, $transfer->handle());
                        $active[spl_object_id($transfer->handle())] = $transfer;
                    } catch (\Throwable $error) {
                        if (isset($sink)) {
                            $sink->abort();
                        }
                        $report->addFailure(new Failure($key, $url, 'preparation', $error->getMessage()));
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
                    /** @var CurlMultiHandle $multi */
                    $handle = $completed['handle'];
                    $id = spl_object_id($handle);
                    $transfer = $active[$id] ?? null;
                    if ($transfer === null) {
                        continue;
                    }

                    unset($active[$id]);
                    curl_multi_remove_handle($multi, $handle);
                    $this->completeTransfer($report, $transfer, $completed['result']);
                    curl_close($handle);
                }

                if ($active !== []) {
                    $selected = curl_multi_select($multi, 1.0);
                    if ($selected === -1) {
                        usleep(1_000);
                    }
                }
            }
        } finally {
            foreach ($active as $transfer) {
                $transfer->sink()->abort();
                curl_multi_remove_handle($multi, $transfer->handle());
                curl_close($transfer->handle());
            }
            curl_multi_close($multi);
        }

        return $report;
    }

    /** @return list<array{int|string, string}> */
    private function prepareJobs(Report $report): array
    {
        $jobs = [];
        /** @var array<int|string, true> $seen */
        $seen = [];
        foreach ($this->urls as $key => $url) {
            if (isset($seen[$key])) {
                throw new InvalidArgumentException(sprintf('Batch key %s is duplicated.', (string) $key));
            }
            $seen[$key] = true;

            if (!is_string($url) || !$this->isHttpUrl($url)) {
                $report->addFailure(new Failure($key, is_string($url) ? $url : '', 'validation', 'A job URL must be a valid HTTP or HTTPS URL.'));

                continue;
            }
            $jobs[] = [$key, $url];
        }

        return $jobs;
    }

    private function completeTransfer(Report $report, Transfer $transfer, int $curlCode): void
    {
        $status = (int) curl_getinfo($transfer->handle(), CURLINFO_RESPONSE_CODE);
        $duration = (float) curl_getinfo($transfer->handle(), CURLINFO_TOTAL_TIME);
        $sink = $transfer->sink();

        $failureMessage = $sink->failureMessage();
        if ($failureMessage !== null) {
            $sink->abort();
            $report->addFailure(new Failure($transfer->key(), $transfer->url(), 'validation', $failureMessage, $curlCode, $status));

            return;
        }
        if ($curlCode !== CURLE_OK) {
            $sink->abort();
            $type = $curlCode === CURLE_OPERATION_TIMEDOUT ? 'timeout' : 'transport';
            $message = curl_error($transfer->handle());
            if ($message === '') {
                $message = curl_strerror($curlCode) ?? 'Unknown cURL transfer error.';
            }
            $report->addFailure(new Failure(
                $transfer->key(),
                $transfer->url(),
                $type,
                $message,
                $curlCode,
                $status,
            ));

            return;
        }
        if (!$this->accepts($status)) {
            $sink->abort();
            $report->addFailure(new Failure(
                $transfer->key(),
                $transfer->url(),
                'validation',
                sprintf('Unexpected HTTP status %d.', $status),
                null,
                $status,
            ));

            return;
        }

        try {
            $report->addSuccess($sink->finish($transfer->key(), $transfer->url(), $status, $transfer->headers(), $duration));
        } catch (\Throwable $error) {
            $sink->abort();
            $report->addFailure(new Failure($transfer->key(), $transfer->url(), 'preparation', $error->getMessage(), null, $status));
        }
    }

    private function accepts(int $status): bool
    {
        if ($this->acceptedStatuses !== null) {
            return isset($this->acceptedStatuses[$status]);
        }

        return $status >= 200 && $status < 300;
    }

    private function isHttpUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    abstract protected function createSink(int|string $key, string $url): BatchSink;
}
