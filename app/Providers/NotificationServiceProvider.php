<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Notifications\NotificationManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Services\Notifications\Strategies\EmailStrategy;
use App\Services\Notifications\Interfaces\EmailStrategyInterface;
use App\Services\Notifications\Interfaces\NotificationManagerInterface;

class NotificationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        // Bind the main manager interface to its implementation as a singleton
        $this->app->singleton(NotificationManagerInterface::class, NotificationManager::class);
        
        $this->app->singleton(EmailStrategyInterface::class, EmailStrategy::class);

        // Read the config and bind each SMS provider strategy
        $this->registerSmsStrategies();
    }

    protected function registerSmsStrategies(): void
    {
        $smsProviders = config('notifications.sms.providers', []);

        foreach ($smsProviders as $alias => $config) {
            $this->app->bind("sms.provider.{$alias}", function ($app) use ($config) {
                $strategyClass = $config['driver'];
                if (!class_exists($strategyClass)) {
                    throw new \InvalidArgumentException("Strategy class {$strategyClass} not found for SMS provider.");
                }
                return new $strategyClass($config);
            });
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        // **THE FIX**: List ALL bindings this provider is responsible for.
        // This ensures that even if a strategy is requested directly, the
        // deferred provider will be loaded correctly.

        $providerKeys = array_map(
            fn($alias) => "sms.provider.{$alias}",
            array_keys(config('notifications.sms.providers', []))
        );

        return array_merge(
            [NotificationManagerInterface::class],
            $providerKeys
        );
    }
}
