<?php

use Illuminate\Support\Facades\Queue;
use Pyle\Webhooks\Facades\Webhooks;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Pyle\Webhooks\Models\WebhookEndpointEventSubscription;
use Spatie\WebhookServer\CallWebhookJob;

beforeEach(function () {
    Queue::fake();
});

it('dispatches webhook to subscribed endpoints', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'enabled' => true,
    ]);

    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_key' => 'test.event',
    ]);

    Webhooks::dispatch('test.event', ['foo' => 'bar']);

    Queue::assertPushed(CallWebhookJob::class, function ($job) use ($endpoint) {
        return $job->webhookUrl === $endpoint->url;
    });
});

it('does not dispatch to disabled endpoints', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'enabled' => false,
    ]);

    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_key' => 'test.event',
    ]);

    Webhooks::dispatch('test.event', ['foo' => 'bar']);

    Queue::assertNothingPushed();
});

it('does not dispatch when no endpoints are subscribed', function () {
    WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'enabled' => true,
    ]);

    Webhooks::dispatch('test.event', ['foo' => 'bar']);

    Queue::assertNothingPushed();
});

it('dispatches to multiple subscribed endpoints', function () {
    $endpoint1 = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook1',
        'enabled' => true,
    ]);

    $endpoint2 = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook2',
        'enabled' => true,
    ]);

    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint1->id,
        'event_key' => 'test.event',
    ]);

    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint2->id,
        'event_key' => 'test.event',
    ]);

    Webhooks::dispatch('test.event', ['foo' => 'bar']);

    Queue::assertPushed(CallWebhookJob::class, 2);
});
