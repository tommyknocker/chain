<?php

declare(strict_types=1);

namespace tommyknocker\chain;

enum FlowEventType: string
{
    case Started = 'started';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
