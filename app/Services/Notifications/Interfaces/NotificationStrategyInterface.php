<?php

namespace App\Services\Notifications\Interfaces;

interface NotificationStrategyInterface
{
    /**
     * Send a notification.
     *
     * @param string $recipient
     * @param mixed $content (string for SMS, Mailable for email)
     * @throws \App\Services\Notifications\Exceptions\NotificationSendingException
     */
    public function send(string $recipient, mixed $content): void;

    
}
