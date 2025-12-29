<?php

use Pyle\Webhooks\Tests\TestCase;

it('registers spatie webhook server provider', function () {
    $providers = $this->app->getLoadedProviders();

    expect($providers)->toHaveKey(\Spatie\WebhookServer\WebhookServerServiceProvider::class);
});

it('has webhook-server config available after provider registration', function () {
    // Config keys are provided by Spatie's WebhookServerServiceProvider at runtime
    // @phpstan-ignore-next-line - Config provided by Spatie's service provider at runtime
    /** @var array<string, mixed> $webhookServerConfig */
    $webhookServerConfig = config('webhook-server');
    expect($webhookServerConfig)->toBeArray();
    expect($webhookServerConfig['queue'] ?? null)->toBe('default');
    expect($webhookServerConfig['http_verb'] ?? null)->toBe('post');
});

class ConfigOverrideTest extends TestCase
{
    protected function getWebhookServerConfig(): array
    {
        return [
            'queue' => 'custom-queue',
            'timeout_in_seconds' => 10,
        ];
    }

    public function initialize(): void
    {
        $this->setUp();
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

it('applies webhook server config overrides from webhooks config', function () {
    $test = new ConfigOverrideTest('test');
    $test->initialize();

    // @phpstan-ignore-next-line - Accessing protected property via public getter
    $app = $test->getApp();
    // @phpstan-ignore-next-line - Config provided by Spatie's service provider at runtime
    expect($app['config']->get('webhook-server.queue'))->toBe('custom-queue');
    // @phpstan-ignore-next-line - Config provided by Spatie's service provider at runtime
    expect($app['config']->get('webhook-server.timeout_in_seconds'))->toBe(10);
});

class ConfigPartialOverrideTest extends TestCase
{
    protected function getWebhookServerConfig(): array
    {
        return [
            'queue' => 'another-queue',
        ];
    }

    public function initialize(): void
    {
        $this->setUp();
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

it('merges webhook server config overrides recursively', function () {
    $test = new ConfigPartialOverrideTest('test');
    $test->initialize();

    // @phpstan-ignore-next-line - Accessing protected property via public getter
    $app = $test->getApp();
    // @phpstan-ignore-next-line - Config provided by Spatie's service provider at runtime
    expect($app['config']->get('webhook-server.queue'))->toBe('another-queue');
    // @phpstan-ignore-next-line - Config provided by Spatie's service provider at runtime
    expect($app['config']->get('webhook-server.http_verb'))->toBe('post');
    // @phpstan-ignore-next-line - Config provided by Spatie's service provider at runtime
    expect($app['config']->get('webhook-server.tries'))->toBe(3);
});
