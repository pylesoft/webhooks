<?php

use Pyle\Webhooks\EventCatalog;
use Pyle\Webhooks\Tests\TestCase;

class EventCatalogTest extends TestCase
{
    protected function getAdditionalWebhooksConfig(): array
    {
        return [
            'events' => [
                'test.event' => [
                    'event' => \Illuminate\Events\Dispatcher::class,
                    'group' => 'Test',
                    'label' => 'Test Event',
                    'description' => 'A test event',
                ],
            ],
        ];
    }
}

it('can retrieve all configured events', function () {
    $test = new EventCatalogTest('test');
    $test->setUp();

    $catalog = $test->app->make(EventCatalog::class);

    expect($catalog->all())->toBeArray();
});

it('can check if event key exists', function () {
    $test = new EventCatalogTest('test');
    $test->setUp();

    $catalog = $test->app->make(EventCatalog::class);

    expect($catalog->has('test.event'))->toBeTrue();
    expect($catalog->has('nonexistent.event'))->toBeFalse();
});

it('can get event configuration by key', function () {
    $test = new EventCatalogTest('test');
    $test->setUp();

    $catalog = $test->app->make(EventCatalog::class);

    $config = $catalog->get('test.event');

    expect($config)->toBeArray();
    expect($config['event'])->toBe(\Illuminate\Events\Dispatcher::class);
    expect($config['group'])->toBe('Test');
});

it('can get event class for event key', function () {
    $test = new EventCatalogTest('test');
    $test->setUp();

    $catalog = $test->app->make(EventCatalog::class);

    expect($catalog->getEventClass('test.event'))->toBe(\Illuminate\Events\Dispatcher::class);
    expect($catalog->getEventClass('nonexistent.event'))->toBeNull();
});

it('can get metadata for event key', function () {
    $test = new EventCatalogTest('test');
    $test->setUp();

    $catalog = $test->app->make(EventCatalog::class);

    $metadata = $catalog->getMetadata('test.event');

    expect($metadata)->toBeArray();
    expect($metadata['group'])->toBe('Test');
    expect($metadata['label'])->toBe('Test Event');
    expect($metadata['description'])->toBe('A test event');
});
