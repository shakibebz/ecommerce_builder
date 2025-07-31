<?php

use App\Services\Notifications\Strategies\FarazSmsStrategy;
use App\Services\Notifications\Strategies\SmsIrStrategy;

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Notification Settings
    |--------------------------------------------------------------------------
    */
    'sms' => [
        // The default provider to use for sending SMS.
        'default' => env('SMS_PROVIDER', 'faraz_sms'),

        // The order in which to attempt providers in case of failure.
        'failover_order' => [
            'faraz_sms',
            'sms_ir',
        ],

        // Configuration for all available SMS providers.
        'providers' => [
            'faraz_sms' => [
                'driver' => FarazSmsStrategy::class,
                'api_key' => env('FARAZ_SMS_API_KEY', 'your-faraz-sms-api-key'),
                'sender' => env('FARAZ_SMS_SENDER', '500012345'),
                'url' => 'https://api.farazsms.com/v1/sms/send',
            ],
            'sms_ir' => [
                'driver' => SmsIrStrategy::class,
                'api_key' => env('SMSIR_API_KEY', 'your-sms-ir-api-key'),
                'secret' => env('SMS_IR_SECRET', 'your-sms-ir-secret'),
                'url' => 'https://api.sms.ir/v1/send/verify',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Notification Settings
    |--------------------------------------------------------------------------
    */
    'email' => [
        // No specific provider config needed as we use Laravel's default mailer.
    ],
];
