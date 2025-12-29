<?php

use Illuminate\Support\Facades\Schema;

it('creates webhook_endpoints table', function () {
    expect(Schema::hasTable('webhook_endpoints'))->toBeTrue();
});

it('creates webhook_endpoint_event_subscriptions table', function () {
    expect(Schema::hasTable('webhook_endpoint_event_subscriptions'))->toBeTrue();
});

it('webhook_endpoints table has correct columns', function () {
    $columns = Schema::getColumnListing('webhook_endpoints');

    expect($columns)->toContain('id')
        ->toContain('url')
        ->toContain('description')
        ->toContain('enabled')
        ->toContain('secret')
        ->toContain('created_at')
        ->toContain('updated_at');
});

it('webhook_endpoint_event_subscriptions table has correct columns', function () {
    $columns = Schema::getColumnListing('webhook_endpoint_event_subscriptions');

    expect($columns)->toContain('id')
        ->toContain('webhook_endpoint_id')
        ->toContain('event_key')
        ->toContain('created_at')
        ->toContain('updated_at');
});
