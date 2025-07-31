<?php

namespace App\Services\Notifications\Strategies;

use Exception;
use Ipe\Sdk\Facades\SmsIr;
use Illuminate\Support\Facades\Log;
use App\Services\Notifications\Interfaces\SmsStrategyInterface;
use App\Services\Notifications\Exceptions\NotificationSendingException;


class SmsIrStrategy implements SmsStrategyInterface
{
    public function __construct(protected array $config) {}

    public function send(string $recipient, string $message): void
    {
        Log::info("Attempting to send simple SMS via FarazSms to {$recipient}.");
        // ... (simulation or real API call for simple send)
        Log::info("Simple SMS sent successfully via FarazSms.");
    }

    public function sendWithPattern(string $recipient, string $patternCode, array $variables): void
    {
        Log::info("Attempting to send pattern SMS [{$patternCode}] via FarazSms to {$recipient}.");

        if (env('SIMULATE_FARAZ_SMS_FAILURE', false)) {
            throw new NotificationSendingException('FarazSms Pattern API Error (Simulated)');
        }

        // Real implementation would look like this:
        // $payload = [ 'recipient' => $recipient, 'pattern_code' => $patternCode, 'variables' => $variables ];
        // Http::post(..., $payload);

        Log::info("Pattern SMS sent successfully via FarazSms.");
    }

    public function getCredit(): float
    {
        Log::info("Checking credit for FarazSms.");
        // Real implementation would make an API call to a credit endpoint.
        return 15000.0; // Simulated credit amount
    }
}
