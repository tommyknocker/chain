<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class EmailService
{
    public function send(string $to): string
    {
        return "Email sent to $to";
    }

    public function sendBulk(array $recipients): string
    {
        return 'Bulk email sent to ' . count($recipients) . ' recipients';
    }
}
