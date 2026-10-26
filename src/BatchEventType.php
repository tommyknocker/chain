<?php

declare(strict_types=1);

namespace tommyknocker\chain;

enum BatchEventType: string
{
    case Started = 'started';
    case AttemptFailed = 'attempt_failed';
    case Progress = 'progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case Retrying = 'retrying';
}
