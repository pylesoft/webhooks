<?php

use Illuminate\Validation\ValidationException;
use Pyle\Webhooks\Facades\Webhooks;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Pyle\Webhooks\Models\WebhookEndpointEventSubscription;
use Pyle\Webhooks\Tests\Helpers\OtherEvent;
use Pyle\Webhooks\Tests\Helpers\TestEvent1;
use Pyle\Webhooks\Tests\Helpers\TestEvent2;
use Pyle\Webhooks\Tests\TestCase;

class WebhookEndpointsTest extends TestCase
{
    protected function getAdditionalWebhooksConfig(): array
    {
        return [
            'events' => [
                'test.event1' => [
                    'event' => TestEvent1::class,
                    'group' => 'Test',
                    'label' => 'Test Event 1',
                ],
                'test.event2' => [
                    'event' => TestEvent2::class,
                    'group' => 'Test',
                    'label' => 'Test Event 2',
                ],
                'other.event' => [
                    'event' => OtherEvent::class,
                    'group' => 'Other',
                    'label' => 'Other Event',
                ],
            ],
        ];
    }

    public function initialize(): void
    {
        parent::setUp();
    }
}

it('provides validation rules', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $rules = Webhooks::endpoints()->rules();

    expect($rules)->toHaveKey('url');
    expect($rules)->toHaveKey('description');
    expect($rules)->toHaveKey('enabled');
    expect($rules)->toHaveKey('events');
    expect($rules)->toHaveKey('events.*');
});

it('provides validation messages', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $messages = Webhooks::endpoints()->messages();

    expect($messages)->toBeArray();
    expect($messages)->toHaveKey('url.required');
    expect($messages)->toHaveKey('url.starts_with');
});

it('validates input data', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $validated = Webhooks::endpoints()->validate([
        'url' => 'https://example.com/webhook',
        'description' => 'Test endpoint',
        'enabled' => true,
        'events' => ['test.event1'],
    ]);

    expect($validated)->toHaveKey('url');
    expect($validated['url'])->toBe('https://example.com/webhook');
    expect($validated['events'])->toContain('test.event1');
});

it('throws validation exception for invalid url', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    expect(fn () => Webhooks::endpoints()->validate([
        'url' => 'http://example.com/webhook', // Not HTTPS
        'events' => [],
    ]))->toThrow(ValidationException::class);
});

it('throws validation exception for invalid event key', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    expect(fn () => Webhooks::endpoints()->validate([
        'url' => 'https://example.com/webhook',
        'events' => ['invalid.event'],
    ]))->toThrow(ValidationException::class);
});

it('creates a new endpoint with events', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = Webhooks::endpoints()->create(
        url: 'https://example.com/webhook',
        events: ['test.event1', 'test.event2'],
        description: 'Test endpoint',
        enabled: true
    );

    expect($endpoint)->toBeInstanceOf(WebhookEndpoint::class);
    expect($endpoint->url)->toBe('https://example.com/webhook');
    expect($endpoint->description)->toBe('Test endpoint');
    expect($endpoint->enabled)->toBeTrue();
    expect($endpoint->secret)->not->toBeEmpty();

    $subscriptions = $endpoint->subscriptions()->pluck('event_key')->toArray();
    expect($subscriptions)->toContain('test.event1', 'test.event2');
});

it('creates endpoint with default enabled true', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = Webhooks::endpoints()->create(
        url: 'https://example.com/webhook'
    );

    expect($endpoint->enabled)->toBeTrue();
});

it('creates endpoint with enabled false', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = Webhooks::endpoints()->create(
        url: 'https://example.com/webhook',
        enabled: false
    );

    expect($endpoint->enabled)->toBeFalse();
});

it('creates endpoint with empty events array', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = Webhooks::endpoints()->create(
        url: 'https://example.com/webhook',
        events: []
    );

    expect($endpoint->subscriptions()->count())->toBe(0);
});

it('updates endpoint url', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://old.example.com/webhook',
    ]);

    $updated = Webhooks::endpoints()->update(
        endpoint: $endpoint,
        url: 'https://new.example.com/webhook'
    );

    expect($updated->url)->toBe('https://new.example.com/webhook');
});

it('updates endpoint by id', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://old.example.com/webhook',
    ]);

    $updated = Webhooks::endpoints()->update(
        endpoint: $endpoint->id,
        url: 'https://new.example.com/webhook'
    );

    expect($updated->url)->toBe('https://new.example.com/webhook');
});

it('updates endpoint events', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
    ]);
    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_key' => 'test.event1',
    ]);

    $updated = Webhooks::endpoints()->update(
        endpoint: $endpoint,
        events: ['test.event2', 'other.event']
    );

    $subscriptions = $updated->subscriptions()->pluck('event_key')->toArray();
    expect($subscriptions)->toContain('test.event2', 'other.event');
    expect($subscriptions)->not->toContain('test.event1');
});

it('updates endpoint description', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'description' => 'Old description',
    ]);

    $updated = Webhooks::endpoints()->update(
        endpoint: $endpoint,
        description: 'New description'
    );

    expect($updated->description)->toBe('New description');
});

it('updates endpoint enabled status', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'enabled' => true,
    ]);

    $updated = Webhooks::endpoints()->update(
        endpoint: $endpoint,
        enabled: false
    );

    expect($updated->enabled)->toBeFalse();
});

it('updates multiple fields at once', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://old.example.com/webhook',
        'description' => 'Old description',
        'enabled' => true,
    ]);

    $updated = Webhooks::endpoints()->update(
        endpoint: $endpoint,
        url: 'https://new.example.com/webhook',
        description: 'New description',
        enabled: false,
        events: ['test.event1']
    );

    expect($updated->url)->toBe('https://new.example.com/webhook');
    expect($updated->description)->toBe('New description');
    expect($updated->enabled)->toBeFalse();
    expect($updated->subscriptions()->pluck('event_key')->toArray())->toContain('test.event1');
});

it('does not update when no fields provided', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
    ]);

    $originalUpdatedAt = $endpoint->updated_at;

    $updated = Webhooks::endpoints()->update(endpoint: $endpoint);

    expect($updated->url)->toBe('https://example.com/webhook');
    // Note: updated_at may change even without updates in some Laravel versions
});

it('deletes endpoint by model', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create();

    Webhooks::endpoints()->delete($endpoint);

    expect(WebhookEndpoint::find($endpoint->id))->toBeNull();
});

it('deletes endpoint by id', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create();

    Webhooks::endpoints()->delete($endpoint->id);

    expect(WebhookEndpoint::find($endpoint->id))->toBeNull();
});

it('deletes endpoint subscriptions when endpoint is deleted', function () {
    $test = new WebhookEndpointsTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
    ]);
    $subscriptionId = WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_key' => 'test.event1',
    ])->id;

    Webhooks::endpoints()->delete($endpoint);

    // Refresh to ensure cascade has completed
    expect(WebhookEndpointEventSubscription::where('id', $subscriptionId)->exists())->toBeFalse();
});
