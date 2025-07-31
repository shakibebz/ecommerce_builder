<?php

namespace App\Services\Notifications\Strategies;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Mail\Mailer;
use App\Services\Notifications\Interfaces\EmailStrategyInterface;
use App\Services\Notifications\Exceptions\NotificationSendingException;


class EmailStrategy implements EmailStrategyInterface
{
    public function __construct(protected Mailer $mailer) {}

    public function send(string $recipient, mixed $content): void
    {
        if (!($content instanceof Mailable)) {
            throw new \InvalidArgumentException('Email content must be an instance of Illuminate\Mail\Mailable.');
        }

        try {
            Log::info("Attempting to send email to {$recipient}.");
            $this->mailer->to($recipient)->send($content);
            Log::info("Email sent successfully to {$recipient}.");
        } catch (\Exception $e) {
            throw new NotificationSendingException('Failed to send email: ' . $e->getMessage());
        }
    }
}
