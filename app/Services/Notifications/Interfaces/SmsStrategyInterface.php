<?php

namespace App\Services\Notifications\Interfaces;

use App\Services\Notifications\Exceptions\NotificationSendingException;

interface SmsStrategyInterface
{
    /**
     * Send a simple text message.
     *
     * @param string $recipient
     * @param string $message
     * @throws NotificationSendingException
     */
    public function send(string $recipient, string $message): void;

    /**
     * Send a message using a pre-defined provider pattern/template.
     *
     * @param string $recipient
     * @param string $patternCode
     * @param array $variables Key-value pairs for the pattern.
     * @throws NotificationSendingException
     */
    public function sendWithPattern(string $recipient, string $patternCode, array $variables): void;

    /**
     * Get the remaining credit from the provider.
     *
     * @return float The remaining credit.
     * @throws \Exception if the API call fails.
     */
    public function getCredit(): float;
}
