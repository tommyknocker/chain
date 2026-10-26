<?php

declare(strict_types=1);

namespace tommyknocker\chain;

/**
 * Convenience entry points for bounded concurrent HTTP work.
 *
 * Batch is independent from Chain: it schedules cURL transfers and returns a Report.
 */
final class Batch
{
    /**
     * Fetch HTTP response bodies into memory.
     *
     * Input keys are retained in the resulting Report.
     *
     * @param iterable<int|string, string> $urls
     */
    public static function fetch(iterable $urls): FetchBatch
    {
        return new FetchBatch($urls);
    }

    /**
     * Download HTTP responses to files. Call toDirectory() before run().
     *
     * Input keys are retained in the resulting Report.
     *
     * @param iterable<int|string, string> $urls
     */
    public static function download(iterable $urls): DownloadBatch
    {
        return new DownloadBatch($urls);
    }
}
