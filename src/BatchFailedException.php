<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use RuntimeException;

final class BatchFailedException extends RuntimeException
{
    public function __construct(private readonly Report $report)
    {
        parent::__construct(sprintf('%d batch request(s) failed.', count($report->failures())));
    }

    public function report(): Report
    {
        return $this->report;
    }
}
