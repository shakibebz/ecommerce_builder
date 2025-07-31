<?php

namespace App\Services\Notifications\Interfaces;

use App\Services\Notifications\Exceptions\NotificationSendingException;
use Illuminate\Mail\Mailable;

interface EmailStrategyInterface
{
    /**
     * Send an email using a Mailable object.
     *
     * @param string $recipient
     * @param Mailable $mailable
     * @throws NotificationSendingException
     */
    public function send(string $recipient, Mailable $mailable): void;
}
