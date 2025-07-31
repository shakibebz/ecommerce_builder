<?php

namespace App\Services\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use App\Services\Notifications\Strategies\EmailStrategy;
use App\Services\Notifications\Interfaces\SmsStrategyInterface;
use App\Services\Notifications\Exceptions\AllProvidersFailedException;
use App\Services\Notifications\Exceptions\NotificationSendingException;
use App\Services\Notifications\Interfaces\NotificationManagerInterface;

class NotificationManager implements NotificationManagerInterface
{
    protected ?string $channel = null;
    protected ?string $recipient = null;

    public function __construct(
        protected Container $app,
        protected Repository $config
    ) {}

    public function via(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function to(string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function send(mixed $content): void
    {
        if (is_null($this->channel) || is_null($this->recipient)) {
            throw new \InvalidArgumentException('Channel and recipient must be set before sending.');
        }

        switch ($this->channel) {
            case 'sms':
                if (!is_string($content)) {
                    throw new \InvalidArgumentException('SMS content must be a string.');
                }
                $this->sendSmsWithFailover($this->recipient, $content);
                break;
            case 'email':
                if (!($content instanceof Mailable)) {
                    throw new \InvalidArgumentException('Email content must be a Mailable instance.');
                }
                $this->sendEmail($this->recipient, $content);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported channel [{$this->channel}].");
        }
    }



    public function sendSmsWithPattern(string $recipient, string $patternCode, array $variables): void
    {
        $providers = $this->config->get('notifications.sms.failover_order', []);
        $lastException = null;

        dd($providers);

        foreach ($providers as $providerAlias) {
            try {
                /** @var SmsStrategyInterface $strategy */
                $strategy = $this->app->make("sms.provider.{$providerAlias}");
                $strategy->sendWithPattern($recipient, $patternCode, $variables);

                Log::info("Pattern SMS sent successfully to {$recipient} via {$providerAlias}.");
                return; // Success!

            } catch (NotificationSendingException $e) {
                $lastException = $e;

                // THIS IS THE IMPORTANT CHANGE: Add the exception context to the log
                Log::warning(
                    "Pattern SMS provider [{$providerAlias}] failed for recipient {$recipient}. Attempting failover.",
                    ['exception' => $e] // This will log the full stack trace of the original error
                );
                continue;
            }
        }

        throw new AllProvidersFailedException("All SMS providers failed for pattern SMS to {$recipient}.", 0, $lastException);
    }


    protected function sendEmail(mixed $content): void
    {
        $strategy = $this->app->make(EmailStrategy::class);
        $strategy->send($this->recipient, $content);
    }


    protected function sendSmsWithFailover(string $recipient, string $message): void
    {
        $providers = $this->config->get('notifications.sms.failover_order', []);
        $lastException = null;

        foreach ($providers as $providerAlias) {
            try {
                /** @var SmsStrategyInterface $strategy */
                $strategy = $this->app->make("sms.provider.{$providerAlias}");
                $strategy->send($recipient, $message);
                Log::info("Simple SMS sent successfully to {$recipient} via {$providerAlias}.");
                return;
            } catch (NotificationSendingException $e) {
                $lastException = $e;
                Log::warning(
                    "Simple SMS provider [{$providerAlias}] failed for {$recipient}. Attempting failover.",
                    ['exception' => $e] // Also add it here for simple send
                );
                continue;
            }
        }
        throw new AllProvidersFailedException("All SMS providers failed for simple SMS to {$recipient}.", 0, $lastException);
    }


    public function checkSmsCredit(): float
    {
        $defaultProvider = $this->config->get('notifications.sms.default');

        /** @var SmsStrategyInterface $strategy */
        $strategy = $this->app->make("sms.provider.{$defaultProvider}");

        return $strategy->getCredit();
    }
}
