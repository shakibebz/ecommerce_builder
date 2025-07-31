<?php
namespace App\Services\Payment;


use Illuminate\Testing\Exceptions\InvalidArgumentException;

class PaymentFactory
{
    public static function make(string $gateway): PaymentServiceInterface
    {
        return match ($gateway) {
            'payping' => new PayPingService(),
         //   'zarinpal' => new ZarinpalService(), add another payment gateway
            default => throw new InvalidArgumentException("Unknown gateway: $gateway"),
        };
    }
}
