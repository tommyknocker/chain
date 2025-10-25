<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests\fixtures;

final class NotificationService
{
    public function notify(string $message): string
    {
        return "Notification: $message";
    }

    public function notifyUrgent(string $message): string
    {
        return "URGENT: $message";
    }
}
