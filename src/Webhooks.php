<?php

namespace Pyle\Webhooks;

class Webhooks
{
    public function __construct(
        protected WebhookDispatcher $dispatcher
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
}
