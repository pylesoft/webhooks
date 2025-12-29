<?php

namespace Pyle\Webhooks\Listeners;

use Pyle\Webhooks\EventCatalog;
use Pyle\Webhooks\WebhookDispatcher;

class DispatchWebhookListener
{
    public function __construct(
        protected WebhookDispatcher $dispatcher,
        protected EventCatalog $catalog
    ) {}

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Resolve event key from the event object
        $eventKey = $this->catalog->getEventKey($event);

        if ($eventKey === null) {
            return;
        }

        // Dispatch webhook
        $this->dispatcher->dispatch($eventKey, $event);
    }
}
