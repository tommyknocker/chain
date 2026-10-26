<?php

declare(strict_types=1);

namespace tommyknocker\chain\Internal;

use RuntimeException;
use tommyknocker\chain\BatchResult;
use tommyknocker\chain\Download;

final class FileSink implements BatchSink
{
    /** @var resource */
    private $stream;
    private string $temporaryPath;
    private int $bytes = 0;
    private ?string $failureMessage = null;

    public function __construct(private readonly string $path, bool $overwrite, private readonly ?int $maxBytes = null)
    {
        if (file_exists($path) && !$overwrite) {
            throw new RuntimeException(sprintf('Target file already exists: %s', $path));
        }

        $temporaryPath = tempnam(dirname($path), '.batch-');
        if ($temporaryPath === false) {
            throw new RuntimeException(sprintf('Could not create a temporary file in %s.', dirname($path)));
        }

        $stream = fopen($temporaryPath, 'wb');
        if ($stream === false) {
            @unlink($temporaryPath);

            throw new RuntimeException(sprintf('Could not open temporary file %s.', $temporaryPath));
        }

        $this->temporaryPath = $temporaryPath;
        $this->stream = $stream;
    }

    public function write(string $chunk): int
    {
        if ($this->maxBytes !== null && $this->bytes + strlen($chunk) > $this->maxBytes) {
            $this->failureMessage = sprintf('Downloaded file exceeds the %d byte limit.', $this->maxBytes);

            return 0;
        }
        $written = fwrite($this->stream, $chunk);
        if ($written === false || $written !== strlen($chunk)) {
            $this->failureMessage = 'Could not write the complete response to the temporary file.';

            return 0;
        }

        $this->bytes += $written;

        return $written;
    }

    /** @param array<string, list<string>> $headers */
    public function finish(int|string $key, string $url, int $status, array $headers, float $duration): BatchResult
    {
        fclose($this->stream);
        if (!rename($this->temporaryPath, $this->path)) {
            @unlink($this->temporaryPath);

            throw new RuntimeException(sprintf('Could not move downloaded file to %s.', $this->path));
        }

        return new Download($key, $url, $status, $headers, $duration, $this->path, $this->bytes);
    }

    public function abort(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        if (file_exists($this->temporaryPath)) {
            @unlink($this->temporaryPath);
        }
    }

    public function failureMessage(): ?string
    {
        return $this->failureMessage;
    }
}
