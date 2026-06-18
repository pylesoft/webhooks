<?php

namespace Pyle\Webhooks;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Pyle\Webhooks\Console\Commands\MakeWebhookTransformerCommand;
use Pyle\Webhooks\Listeners\DispatchWebhookListener;
use Spatie\WebhookServer\WebhookServerServiceProvider;

class WebhooksServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if (!$this->packageIsEnabled()) {
            return;
        }
        // Apply webhook-server config overrides from our package config
        // This runs after all providers have registered, ensuring Spatie's config is loaded
        $overrides = config('webhooks.webhook_server', []);
        if (!empty($overrides)) {
            $currentConfig = config('webhook-server', []);
            config(['webhook-server' => array_replace_recursive($currentConfig, $overrides)]);
        }

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'webhooks');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'pyle-webhooks');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Livewire::addLocation(
            classNamespace: 'Pyle\\Webhooks\\Livewire',
        );

        // Load routes if UI is enabled
        if (config('webhooks.ui.enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // Register event listeners for configured events
        $this->registerEventListeners();
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/webhooks.php', 'webhooks');

        if (!$this->packageIsEnabled()) {
            return;
        }

        // Register Spatie's webhook-server provider
        $this->app->register(WebhookServerServiceProvider::class);

        // Register services
        $this->app->singleton(EventCatalog::class);
        $this->app->singleton(PayloadBuilder::class);
        $this->app->singleton(WebhookDispatcher::class);
        $this->app->singleton(WebhookEndpointManager::class);

        // Register the service the package provides.
        $this->app->singleton('webhooks', function ($app) {
            return new Webhooks(
                $app->make(WebhookDispatcher::class),
                $app->make(WebhookEndpointManager::class)
            );
        });
    }

    /**
     * Register event listeners for configured events.
     */
    protected function registerEventListeners(): void
    {
        $events = config('webhooks.events', []);

        foreach ($events as $eventKey => $config) {
            $eventClass = $config['event'] ?? null;

            if ($eventClass && class_exists($eventClass)) {
                Event::listen($eventClass, DispatchWebhookListener::class);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['webhooks'];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/webhooks.php' => config_path('webhooks.php'),
        ], 'webhooks.config');

        // Registering package commands.
        $this->commands([
            MakeWebhookTransformerCommand::class,
        ]);
    }

    private function packageIsEnabled(): bool
    {
        return (bool) config('webhooks.enabled', true);
    }
}
