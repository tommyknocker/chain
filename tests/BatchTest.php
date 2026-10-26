<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\AbstractBatch;
use tommyknocker\chain\Batch;
use tommyknocker\chain\BatchEvent;
use tommyknocker\chain\BatchEventType;
use tommyknocker\chain\BatchFailedException;
use tommyknocker\chain\FailureType;
use tommyknocker\chain\HttpRequest;
use tommyknocker\chain\HttpRetryPolicy;
use tommyknocker\chain\Internal\BatchSink;
use tommyknocker\chain\Internal\MemorySink;

final class BatchTest extends TestCase
{
    /** @var resource */
    private $server = null;
    private string $baseUrl;
    private string $downloadDirectory = '';
    private string $retryState = '';

    protected function setUp(): void
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        if ($socket === false) {
            self::markTestSkipped('The current environment does not permit a local HTTP server.');
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        self::assertIsString($address);

        $router = __DIR__ . '/fixtures/batch-router.php';
        $this->server = proc_open(
            [PHP_BINARY, '-S', $address, $router],
            [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']],
            $pipes,
        );
        self::assertIsResource($this->server);
        fclose($pipes[0]);
        $this->baseUrl = 'http://' . $address;
        $this->downloadDirectory = sys_get_temp_dir() . '/chain-batch-' . bin2hex(random_bytes(8));
        $this->retryState = bin2hex(random_bytes(8));

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $connection = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1));
            if ($connection !== false) {
                fclose($connection);

                return;
            }
            usleep(10_000);
        }

        self::fail('Could not start the local HTTP server.');
    }

    protected function tearDown(): void
    {
        if (is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
        }

        if (is_dir($this->downloadDirectory)) {
            foreach (glob($this->downloadDirectory . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->downloadDirectory);
        }

        if ($this->retryState !== '') {
            @unlink(sys_get_temp_dir() . '/chain-batch-flaky-' . $this->retryState);
        }
    }

    public function testFetchPreservesKeysAndSeparatesFailures(): void
    {
        $report = Batch::fetch([
            'first' => $this->baseUrl . '/first.txt',
            'second' => $this->baseUrl . '/second.txt',
            'missing' => $this->baseUrl . '/missing.txt',
        ])->concurrency(2)->run();

        self::assertEquals(['first' => 'first', 'second' => 'second'], $report->bodies());
        self::assertSame(404, $report->failure('missing')?->status());
        self::assertSame(FailureType::HttpStatus, $report->failure('missing')?->type());
        self::assertFalse($report->allSucceeded());
    }

    public function testFetchCanEnforceAResponseMemoryLimit(): void
    {
        $report = Batch::fetch(['large' => $this->baseUrl . '/large.txt'])
            ->maxBodySize(64)
            ->run();

        self::assertSame([], $report->bodies());
        self::assertStringContainsString('exceeds', $report->failure('large')?->message() ?? '');
    }

    public function testDownloadUsesUrlNamesAndPublishesOnlySuccessfulFiles(): void
    {
        $report = Batch::download([
            101 => $this->baseUrl . '/image.jpg',
            404 => $this->baseUrl . '/missing.txt',
        ])->toDirectory($this->downloadDirectory)->run();

        self::assertSame($this->downloadDirectory . '/image.jpg', $report->download(101)?->path());
        self::assertSame('fake-image', file_get_contents($this->downloadDirectory . '/image.jpg'));
        self::assertSame(404, $report->failure(404)?->status());
        self::assertFileDoesNotExist($this->downloadDirectory . '/missing.txt');
    }

    public function testDownloadCanUseInputKeysAsFileNames(): void
    {
        $report = Batch::download([
            'avatar.jpg' => $this->baseUrl . '/image.jpg',
        ])->toDirectory($this->downloadDirectory)->fileNamesFromKeys()->run()->throwIfFailed();

        self::assertSame($this->downloadDirectory . '/avatar.jpg', $report->download('avatar.jpg')?->path());
    }

    public function testThrowIfFailedExposesTheReport(): void
    {
        $report = Batch::fetch(['missing' => $this->baseUrl . '/missing.txt'])->run();

        try {
            $report->throwIfFailed();
            self::fail('Expected BatchFailedException.');
        } catch (BatchFailedException $error) {
            self::assertSame($report, $error->report());
        }
    }

    public function testRequestSupportsAuthQueryJsonAndContentTypeValidation(): void
    {
        $report = Batch::fetch([
            'auth' => HttpRequest::post($this->baseUrl . '/auth')
                ->bearerToken('test-token')
                ->query(['page' => 2])
                ->json(['event' => 'sync'])
                ->expectJson(),
        ])->run()->throwIfFailed();

        self::assertSame(['query' => ['page' => '2'], 'body' => '{"event":"sync"}'], $report->response('auth')?->json());
    }

    public function testDownloadRejectsUnexpectedContentTypeAndCleansTemporaryFile(): void
    {
        $report = Batch::download(['image' => $this->baseUrl . '/first.txt'])
            ->toDirectory($this->downloadDirectory)
            ->expectContentType('image/*')
            ->run();

        self::assertSame(FailureType::ContentType, $report->failure('image')?->type());
        self::assertFileDoesNotExist($this->downloadDirectory . '/first.txt');
    }

    public function testFailFastStopsAfterAFilePreparationFailure(): void
    {
        mkdir($this->downloadDirectory);
        file_put_contents($this->downloadDirectory . '/taken.jpg', 'existing');

        $report = Batch::download([
            'taken.jpg' => $this->baseUrl . '/image.jpg',
            'later.jpg' => $this->baseUrl . '/image.jpg',
        ])->toDirectory($this->downloadDirectory)->fileNamesFromKeys()->concurrency(1)->failFast()->run();

        self::assertSame(FailureType::Preparation, $report->failure('taken.jpg')?->type());
        self::assertNull($report->download('later.jpg'));
        self::assertFileDoesNotExist($this->downloadDirectory . '/later.jpg');
    }

    public function testRetryWaitsThenRecoversAndEmitsAttemptFailureOnly(): void
    {
        $attemptFailures = [];
        $finalFailures = [];
        $report = Batch::fetch(['flaky' => $this->baseUrl . '/flaky?state=' . $this->retryState])
            ->retry(new HttpRetryPolicy(maxAttempts: 2, initialDelaySeconds: 0.05, maxDelaySeconds: 0.05))
            ->onAttemptFailed(static function (BatchEvent $event) use (&$attemptFailures): void {
                $attemptFailures[] = $event;
            })
            ->onFailed(static function (BatchEvent $event) use (&$finalFailures): void {
                $finalFailures[] = $event;
            })
            ->run();

        self::assertSame(['flaky' => 'recovered'], $report->bodies());
        self::assertCount(1, $attemptFailures);
        self::assertSame(BatchEventType::AttemptFailed, $attemptFailures[0]->type);
        self::assertSame([], $finalFailures);
        self::assertSame('2', file_get_contents(sys_get_temp_dir() . '/chain-batch-flaky-' . $this->retryState));
    }

    public function testSchedulerSleepsAgainWhenNoTransferIsActiveBeforeRetryIsDue(): void
    {
        $batch = new SleepRecordingBatch(['flaky' => $this->baseUrl . '/flaky?state=' . $this->retryState]);
        $report = $batch->retry(new HttpRetryPolicy(maxAttempts: 2, initialDelaySeconds: 1.05, maxDelaySeconds: 1.05))->run();

        self::assertSame(['flaky' => 'recovered'], $report->bodies());
        self::assertGreaterThanOrEqual(2, count($batch->sleeps));
    }

    public function testCancellationFromStartedEventAbortsPendingWork(): void
    {
        $batch = Batch::fetch([
            'first' => $this->baseUrl . '/slow',
            'second' => $this->baseUrl . '/slow',
        ])->concurrency(1);
        $batch->onEvent(static function (BatchEvent $event) use ($batch): void {
            if ($event->type === BatchEventType::Started) {
                $batch->cancel();
            }
        });

        $report = $batch->run();

        self::assertSame([], $report->bodies());
        self::assertSame([], $report->failures());
    }

    public function testConcurrencyRunsTwoSlowRequestsInParallel(): void
    {
        [$server, $baseUrl] = $this->startConcurrentServer();

        try {
            $startedAt = hrtime(true);
            $report = Batch::fetch([
                'first' => $baseUrl . '/slow',
                'second' => $baseUrl . '/slow',
            ])->concurrency(2)->run();
            $elapsed = (hrtime(true) - $startedAt) / 1_000_000_000;

            self::assertEquals(['first' => 'slow', 'second' => 'slow'], $report->bodies());
            self::assertLessThan(0.45, $elapsed, 'Two 250 ms requests should not run sequentially.');
        } finally {
            proc_terminate($server);
            proc_close($server);
        }
    }

    /** @return array{resource, string} */
    private function startConcurrentServer(): array
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($socket);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        self::assertIsString($address);

        $server = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/concurrent-http-server.php', $address],
            [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']],
            $pipes,
        );
        self::assertIsResource($server);
        fclose($pipes[0]);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $connection = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1));
            if ($connection !== false) {
                fclose($connection);

                return [$server, 'http://' . $address];
            }
            usleep(10_000);
        }

        proc_terminate($server);
        proc_close($server);
        self::fail('Could not start the concurrent HTTP server.');
    }
}

final class SleepRecordingBatch extends AbstractBatch
{
    /** @var list<int> */
    public array $sleeps = [];

    protected function createSink(int|string $key, HttpRequest $request): BatchSink
    {
        return new MemorySink(1024);
    }

    protected function sleep(int $microseconds): void
    {
        $this->sleeps[] = $microseconds;
        parent::sleep($microseconds);
    }
}
