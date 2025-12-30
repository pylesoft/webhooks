<?php

namespace Pyle\Webhooks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void dispatch(string $eventKey, ?array $data = null, array $meta = [])
 * @method static \Pyle\Webhooks\WebhookEndpointManager endpoints()
 */
class Webhooks extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'webhooks';
    }
}
