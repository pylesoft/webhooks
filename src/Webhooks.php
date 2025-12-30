<?php

namespace Pyle\Webhooks;

use Pyle\Webhooks\Endpoints\WebhookEndpoints;

class Webhooks
{
    public function __construct(
        protected WebhookDispatcher $dispatcher,
        protected WebhookEndpoints $endpoints
    ) {}

    /**
     * Dispatch webhooks for a given event key.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public function dispatch(string $eventKey, ?array $data = null, array $meta = []): void
    {
        $this->dispatcher->dispatch($eventKey, null, $data, $meta);
    }

    /**
     * Get the webhook endpoints manager.
     */
    public function endpoints(): WebhookEndpoints
    {
        return $this->endpoints;
    }
}
