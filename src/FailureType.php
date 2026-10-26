<?php

declare(strict_types=1);

namespace tommyknocker\chain;

enum FailureType: string
{
    case Transport = 'transport';
    case Timeout = 'timeout';
    case HttpStatus = 'http_status';
    case ContentType = 'content_type';
    case SizeLimit = 'size_limit';
    case Validation = 'validation';
    case Preparation = 'preparation';
    case RetryExhausted = 'retry_exhausted';
}
