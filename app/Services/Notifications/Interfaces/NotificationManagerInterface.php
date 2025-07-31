<?php

namespace App\Services\Notifications\Interfaces;

interface NotificationManagerInterface
{
    /**
     * Set the notification channel.
     *
     * @param string $channel ('sms' or 'email')
     * @return self
     */
    public function via(string $channel): self;

    /**
     * Set the recipient.
     *
     * @param string $recipient
     * @return self
     */
    public function to(string $recipient): self;

    /**
     * Send the notification using the selected channel and strategy.
     *
     * @param mixed $content
     * @throws \App\Services\Notifications\Exceptions\AllProvidersFailedException
     */
    public function send(mixed $content): void;


    public function sendSmsWithPattern(string $recipient, string $patternCode, array $variables): void;
    public function checkSmsCredit(): float;
}
