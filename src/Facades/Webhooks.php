<?php

namespace Pyle\Webhooks\Facades;

use Illuminate\Support\Facades\Facade;

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
