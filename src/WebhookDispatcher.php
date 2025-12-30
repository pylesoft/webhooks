<?php

namespace Pyle\Webhooks;

use Illuminate\Support\Facades\Log;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Spatie\WebhookServer\WebhookCall;

class WebhookDispatcher
{
    public function __construct(
        protected PayloadBuilder $payloadBuilder,
        protected EventCatalog $catalog
    ) {}

    /**
     * Dispatch webhooks for a given event key.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public function dispatch(string $eventKey, ?object $event = null, ?array $data = null, array $meta = []): void
    {
        // Find all enabled endpoints subscribed to this event
        $endpoints = WebhookEndpoint::query()
            ->where('enabled', true)
            ->whereHas('subscriptions', function ($query) use ($eventKey) {
                $query->where('event_key', $eventKey);
            })
            ->get();

        if ($endpoints->isEmpty()) {
            return;
        }

        // Build the payload envelope
        $payload = $this->payloadBuilder->buildFromEvent($eventKey, $event, $data, $meta);

        // Dispatch to each endpoint
        foreach ($endpoints as $endpoint) {
            $this->dispatchToEndpoint($endpoint, $payload);
        }
    }

    /**
     * Dispatch webhook to a specific endpoint.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function dispatchToEndpoint(WebhookEndpoint $endpoint, array $payload): void
    {
        try {
            WebhookCall::create()
                ->url($endpoint->url)
                ->payload($payload)
                ->useSecret($endpoint->secret)
                ->dispatch();
        }
        catch (\Exception $e) {
            Log::error('Failed to dispatch webhook', [
                'endpoint_id' => $endpoint->id,
                'url' => $endpoint->url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
