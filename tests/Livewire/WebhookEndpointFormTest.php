<?php

use Livewire\Livewire;
use Pyle\Webhooks\Livewire\WebhookEndpointForm;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Pyle\Webhooks\Models\WebhookEndpointEventSubscription;
use Pyle\Webhooks\Tests\Helpers\OtherEvent;
use Pyle\Webhooks\Tests\Helpers\TestEvent1;
use Pyle\Webhooks\Tests\Helpers\TestEvent2;
use Pyle\Webhooks\Tests\TestCase;

/**
 * Intelephense may incorrectly report Livewire's testing assertion methods as non-public due to
 * upstream method declarations omitting explicit visibility.
 *
 * @return mixed
 */
function testLivewireForm(string $component, array $params = [])
{
    return Livewire::test($component, $params);
}

class WebhookEndpointFormTest extends TestCase
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

it('resets form state on prepareCreate', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    $component = testLivewireForm(WebhookEndpointForm::class)
        ->set('url', 'https://example.com/webhook')
        ->set('description', 'Test')
        ->set('selectedEventKeys', ['test.event1'])
        ->call('prepareCreate');

    expect($component->get('endpointId'))->toBeNull();
    expect($component->get('url'))->toBe('');
    expect($component->get('description'))->toBeNull();
    expect($component->get('selectedEventKeys'))->toBe([]);
});

it('loads endpoint data on prepareEdit', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'description' => 'Test endpoint',
        'enabled' => false,
    ]);

    WebhookEndpointEventSubscription::factory()->create([
        'webhook_endpoint_id' => $endpoint->id,
        'event_key' => 'test.event1',
    ]);

    $component = testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareEdit', $endpoint->id);

    expect($component->get('endpointId'))->toBe($endpoint->id);
    expect($component->get('url'))->toBe('https://example.com/webhook');
    expect($component->get('description'))->toBe('Test endpoint');
    expect($component->get('enabled'))->toBeFalse();
    expect($component->get('selectedEventKeys'))->toContain('test.event1');
});

it('creates a new endpoint with subscriptions', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareCreate')
        ->set('url', 'https://example.com/webhook')
        ->set('description', 'Test endpoint')
        ->set('enabled', true)
        ->set('selectedEventKeys', ['test.event1', 'test.event2'])
        ->call('save');

    $endpoint = WebhookEndpoint::where('url', 'https://example.com/webhook')->first();
    expect($endpoint)->not->toBeNull();
    expect($endpoint->description)->toBe('Test endpoint');
    expect($endpoint->enabled)->toBeTrue();

    $subscriptions = $endpoint->subscriptions()->pluck('event_key')->toArray();
    expect($subscriptions)->toContain('test.event1', 'test.event2');
});

it('updates an existing endpoint', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'description' => 'Old description',
        'enabled' => true,
    ]);

    testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareEdit', $endpoint->id)
        ->set('url', 'https://example.com/new-webhook')
        ->set('description', 'New description')
        ->set('enabled', false)
        ->set('selectedEventKeys', ['test.event1'])
        ->call('save');

    $endpoint->refresh();
    expect($endpoint->url)->toBe('https://example.com/new-webhook');
    expect($endpoint->description)->toBe('New description');
    expect($endpoint->enabled)->toBeFalse();
});

it('validates url is required and must be https', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareCreate')
        ->set('url', 'http://example.com/webhook')
        ->call('save')
        ->assertHasErrors(['url' => 'starts_with']);

    testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareCreate')
        ->set('url', '')
        ->call('save')
        ->assertHasErrors(['url' => 'required']);
});

it('validates event keys exist in catalog', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareCreate')
        ->set('url', 'https://example.com/webhook')
        ->set('selectedEventKeys', ['invalid.event'])
        ->call('save')
        ->assertHasErrors(['selectedEventKeys']);
});

it('selects all events', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    $component = testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareCreate')
        ->call('selectAllEvents');

    expect($component->get('selectedEventKeys'))->toContain('test.event1', 'test.event2', 'other.event');
});

it('selects all events in a group', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    $component = testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareCreate')
        ->call('selectGroup', 'Test');

    expect($component->get('selectedEventKeys'))->toContain('test.event1', 'test.event2');
    expect($component->get('selectedEventKeys'))->not->toContain('other.event');
});

it('filters events by search', function () {
    $test = new WebhookEndpointFormTest('test');
    $test->initialize();

    $component = testLivewireForm(WebhookEndpointForm::class)
        ->call('prepareCreate')
        ->set('eventSearch', 'Test');

    $view = $component->viewData('groupedEvents');
    expect($view)->toHaveKey('Test');
    expect($view)->not->toHaveKey('Other');
});
