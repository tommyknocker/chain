<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\chain\Internal\FileSink;

final class FileSinkTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/chain-file-sink-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->directory) ?: [] as $name) {
            if ($name !== '.' && $name !== '..') {
                unlink($this->directory . '/' . $name);
            }
        }
        rmdir($this->directory);
    }

    public function testPublicationNeverReplacesAFileCreatedAfterPreparation(): void
    {
        $path = $this->directory . '/report.txt';
        $sink = new FileSink($path, false);
        self::assertSame(8, $sink->write('download'));
        file_put_contents($path, 'created by another process');

        try {
            $sink->finish('report', 'https://example.test/report.txt', 200, [], 0.01);
            self::fail('Expected publication without overwrite to fail.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('without replacing', $error->getMessage());
        }

        self::assertSame('created by another process', file_get_contents($path));
    }
}
