<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Pyle\Webhooks\Models\WebhookEndpointEventSubscription;
use Spatie\WebhookServer\CallWebhookJob;

class TestEvent
{
    public function __construct(
        public string $message
    ) {}
}

beforeEach(function () {
    Queue::fake();

    // Configure events for this test
    config([
        'webhooks.events' => [
            'test.event' => [
                'event' => TestEvent::class,
                'group' => 'Test',
                'label' => 'Test Event',
            ],
        ],
    ]);

    // Re-register event listeners with the new config
    $events = config('webhooks.events', []);
    foreach ($events as $eventKey => $config) {
        $eventClass = $config['event'] ?? null;
        if ($eventClass && class_exists($eventClass)) {
            Event::listen($eventClass, \Pyle\Webhooks\Listeners\DispatchWebhookListener::class);
        }
    }
});

it('dispatches webhook when configured event is fired', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'enabled' => true,
    ]);

    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_key' => 'test.event',
    ]);

    Event::dispatch(new TestEvent('Hello World'));

    Queue::assertPushed(CallWebhookJob::class, function ($job) use ($endpoint) {
        return $job->webhookUrl === $endpoint->url;
    });
});

it('does not dispatch webhook for unconfigured events', function () {
    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'enabled' => true,
    ]);

    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_key' => 'other.event',
    ]);

    Event::dispatch(new TestEvent('Hello World'));

    Queue::assertNothingPushed();
});
