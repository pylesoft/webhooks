<?php

namespace Pyle\Webhooks\Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Pyle\Webhooks\WebhooksServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            WebhooksServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Set app key for testing
        $app['config']->set('app.key', 'base64:' . base64_encode(
            \Illuminate\Support\Str::random(32)
        ));

        // Set minimal config for webhooks
        $app['config']->set('webhooks', array_merge([
            'secret' => 'test-secret',
            'webhook_server' => $this->getWebhookServerConfig(),
        ], $this->getAdditionalWebhooksConfig()));
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Get webhook server config overrides for testing.
     *
     * @return array<string, mixed>
     */
    protected function getWebhookServerConfig(): array
    {
        return [];
    }

    /**
     * Get additional webhooks config for testing.
     *
     * @return array<string, mixed>
     */
    protected function getAdditionalWebhooksConfig(): array
    {
        return [];
    }

    /**
     * Get the application instance (public accessor for testing).
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApp()
    {
        return $this->app;
    }
}
