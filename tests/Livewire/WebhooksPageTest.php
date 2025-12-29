<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Livewire\Livewire;
use Pyle\Webhooks\Livewire\WebhooksPage;
use Pyle\Webhooks\Models\WebhookEndpoint;
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
function testLivewire(string $component, array $params = [])
{
    return Livewire::test($component, $params);
}

class WebhooksPageTest extends TestCase
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

it('renders the webhooks page', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    testLivewire(WebhooksPage::class)
        ->assertSuccessful()
        ->assertSee('Webhooks')
        ->assertSee('Add endpoint');
});

it('displays empty state when no endpoints exist', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    testLivewire(WebhooksPage::class)
        ->assertSee('No endpoints')
        ->assertSee('Get started by creating a new webhook endpoint');
});

it('displays existing endpoints', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
        'description' => 'Test endpoint',
        'enabled' => true,
    ]);

    testLivewire(WebhooksPage::class)
        ->assertSee($endpoint->url)
        ->assertSee('Test endpoint')
        ->assertSee('Enabled');
});

it('deletes an endpoint', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create();

    testLivewire(WebhooksPage::class)
        ->call('deleteEndpoint', $endpoint->id)
        ->assertSuccessful();

    expect(WebhookEndpoint::find($endpoint->id))->toBeNull();
});

it('paginates endpoints', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    // Create more endpoints than perPage (default 10)
    WebhookEndpoint::factory()->count(15)->create();

    $component = testLivewire(WebhooksPage::class);
    $endpoints = $component->viewData('endpoints');

    expect($endpoints->count())->toBe(10);
    expect($endpoints->hasMorePages())->toBeTrue();
});

it('sorts endpoints by url', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    WebhookEndpoint::factory()->create(['url' => 'https://z.example.com/webhook']);
    WebhookEndpoint::factory()->create(['url' => 'https://a.example.com/webhook']);

    $component = testLivewire(WebhooksPage::class)
        ->call('sort', 'url');

    $endpoints = $component->viewData('endpoints');
    expect($endpoints->first()->url)->toBe('https://a.example.com/webhook');
    expect($component->get('sortDirection'))->toBe('asc');

    // Toggle to desc
    $component->call('sort', 'url');
    $endpoints = $component->viewData('endpoints');
    expect($endpoints->first()->url)->toBe('https://z.example.com/webhook');
    expect($component->get('sortDirection'))->toBe('desc');
});

it('sorts endpoints by created_at', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    $old = WebhookEndpoint::factory()->create(['created_at' => now()->subDay()]);
    $new = WebhookEndpoint::factory()->create(['created_at' => now()]);

    $component = testLivewire(WebhooksPage::class)
        ->call('sort', 'created_at');

    $endpoints = $component->viewData('endpoints');
    expect($endpoints->first()->id)->toBe($old->id);
    expect($component->get('sortDirection'))->toBe('asc');
});

it('toggles secret reveal', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create();

    $component = testLivewire(WebhooksPage::class)
        ->call('toggleRevealSecret', $endpoint->id)
        ->assertSet('revealedSecrets.' . $endpoint->id, true)
        ->call('toggleRevealSecret', $endpoint->id)
        ->assertSet('revealedSecrets.' . $endpoint->id, false);
});

it('tests endpoint successfully', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
    ]);

    // Mock GuzzleHttp Client to return successful response
    $mockClient = \Mockery::mock(Client::class);
    $mockResponse = new Response(200, [], 'OK');
    $mockClient->shouldReceive('request')
        ->once()
        ->andReturn($mockResponse);

    $test->getApp()->instance(Client::class, $mockClient);

    $component = testLivewire(WebhooksPage::class)
        ->call('testEndpoint', $endpoint->id);

    expect($component->get('lastTestResult'))->not->toBeNull();
    expect($component->get('lastTestResult')['variant'])->toBe('success');
    expect($component->get('lastTestResult')['endpoint_id'])->toBe($endpoint->id);
    expect($component->get('lastTestResult')['heading'])->toContain('successfully');
});

it('tests endpoint with failure', function () {
    $test = new WebhooksPageTest('test');
    $test->initialize();

    $endpoint = WebhookEndpoint::factory()->create([
        'url' => 'https://example.com/webhook',
    ]);

    // Mock GuzzleHttp Client to throw exception
    $mockClient = \Mockery::mock(Client::class);
    $mockRequest = new Request('POST', $endpoint->url);
    $mockException = new RequestException('Connection refused', $mockRequest);
    $mockClient->shouldReceive('request')
        ->once()
        ->andThrow($mockException);

    $test->getApp()->instance(Client::class, $mockClient);

    $component = testLivewire(WebhooksPage::class)
        ->call('testEndpoint', $endpoint->id);

    expect($component->get('lastTestResult'))->not->toBeNull();
    expect($component->get('lastTestResult')['variant'])->toBe('danger');
    expect($component->get('lastTestResult')['endpoint_id'])->toBe($endpoint->id);
    expect($component->get('lastTestResult')['heading'])->toContain('failed');
});
