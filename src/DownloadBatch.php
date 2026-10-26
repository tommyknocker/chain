<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use InvalidArgumentException;
use LogicException;
use tommyknocker\chain\Internal\BatchSink;
use tommyknocker\chain\Internal\FileSink;

final class DownloadBatch extends AbstractBatch
{
    private ?string $directory = null;
    private bool $overwriteExistingFiles = false;
    private ?int $maxDownloadBytes = null;
    private bool $fileNamesFromKeys = false;
    /** @var (callable(int|string, string): string)|null */
    private $fileNameResolver = null;
    /** @var array<string, int|string> */
    private array $reservedPaths = [];

    public function toDirectory(string $directory): self
    {
        if ($directory === '') {
            throw new InvalidArgumentException('Download directory cannot be empty.');
        }

        $trimmedDirectory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->directory = $trimmedDirectory === '' ? DIRECTORY_SEPARATOR : $trimmedDirectory;

        return $this;
    }

    public function fileNamesFromKeys(): self
    {
        $this->fileNamesFromKeys = true;

        return $this;
    }

    /** @param callable(int|string, string): string $resolver */
    public function fileNameUsing(callable $resolver): self
    {
        $this->fileNameResolver = $resolver;

        return $this;
    }

    public function overwrite(): self
    {
        $this->overwriteExistingFiles = true;

        return $this;
    }

    public function maxDownloadSize(int $bytes): self
    {
        if ($bytes < 1) {
            throw new InvalidArgumentException('Maximum download size must be at least 1 byte.');
        }

        $this->maxDownloadBytes = $bytes;

        return $this;
    }

    public function run(): Report
    {
        if ($this->directory === null) {
            throw new LogicException('Call toDirectory() before running a download batch.');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0o777, true) && !is_dir($this->directory)) {
            throw new \RuntimeException(sprintf('Could not create download directory %s.', $this->directory));
        }

        $this->reservedPaths = [];

        return parent::run();
    }

    protected function createSink(int|string $key, HttpRequest $request): BatchSink
    {
        $name = $this->fileName($key, $request->url());
        if (basename($name) !== $name || str_contains($name, "\0") || $name === '' || $name === '.' || $name === '..') {
            throw new InvalidArgumentException('Download file names must be plain file names without directory segments.');
        }

        $path = $this->directory . DIRECTORY_SEPARATOR . $name;
        if (isset($this->reservedPaths[$path]) && $this->reservedPaths[$path] !== $key) {
            throw new \RuntimeException(sprintf('More than one download resolves to %s.', $path));
        }
        $this->reservedPaths[$path] = $key;

        return new FileSink($path, $this->overwriteExistingFiles, $this->maxDownloadBytes);
    }

    private function fileName(int|string $key, string $url): string
    {
        if ($this->fileNameResolver !== null) {
            return ($this->fileNameResolver)($key, $url);
        }
        if ($this->fileNamesFromKeys) {
            return (string) $key;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $name = is_string($path) ? rawurldecode(basename($path)) : '';

        return $name !== '' && $name !== '.' && $name !== '/' ? $name : 'download-' . $key;
    }
}
