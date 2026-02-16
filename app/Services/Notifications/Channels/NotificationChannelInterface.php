<?php

namespace App\Services\Notifications\Channels;

interface NotificationChannelInterface
{
    public function send(string $to, string $message, array $options = []): bool;
}
