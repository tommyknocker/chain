<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Batch;
use tommyknocker\chain\BatchFailedException;
use tommyknocker\chain\FailureType;
use tommyknocker\chain\HttpRequest;

final class BatchTest extends TestCase
{
    /** @var resource */
    private $server = null;
    private string $baseUrl;
    private string $downloadDirectory = '';

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
    }

    public function testFetchPreservesKeysAndSeparatesFailures(): void
    {
        $report = Batch::fetch([
            'first' => $this->baseUrl . '/first.txt',
            'second' => $this->baseUrl . '/second.txt',
            'missing' => $this->baseUrl . '/missing.txt',
        ])->concurrency(2)->run();

        self::assertSame(['first' => 'first', 'second' => 'second'], $report->bodies());
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
}
