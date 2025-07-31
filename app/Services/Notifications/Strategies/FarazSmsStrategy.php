<?php

namespace App\Services\Notifications\Strategies;

use App\Services\Notifications\Exceptions\NotificationSendingException;
use App\Services\Notifications\Interfaces\NotificationStrategyInterface;
use Illuminate\Support\Facades\Log;

class FarazSmsStrategy implements NotificationStrategyInterface
{
    public function __construct(protected array $config) {}

    public function send(string $recipient, mixed $content): void
    {
        Log::info("Attempting to send SMS via FarazSms to {$recipient}.");

        // SIMULATE FAILURE: To test failover, we can force a failure.
        // In a real app, this would be a real API call.
        if (env('SIMULATE_FARAZ_SMS_FAILURE', false)) {
            throw new NotificationSendingException('FarazSms API Error: Insufficient credit (Simulated)');
        }

        // --- Real implementation example ---
        // try {
        //     $response = Http::withHeaders(['apikey' => $this->config['api_key']])
        //         ->post($this->config['url'], [
        //             'recipient' => $recipient,
        //             'sender' => $this->config['sender'],
        //             'message' => $content,
        //         ]);
        //
        //     if ($response->failed()) {
        //         throw new NotificationSendingException('FarazSms API Error: ' . $response->body());
        //     }
        // } catch (\Exception $e) {
        //     throw new NotificationSendingException('FarazSms communication error: ' . $e->getMessage());
        // }
        // ---------------------------------

        Log::info("SMS sent successfully via FarazSms to {$recipient}.");
    }
}
