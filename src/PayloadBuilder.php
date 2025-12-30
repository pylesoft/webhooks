<?php

namespace Pyle\Webhooks;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Pyle\Webhooks\Contracts\WebhookPayloadTransformer;

class PayloadBuilder
{
    public function __construct(
        protected EventCatalog $catalog
    ) {
    }

    /**
     * Build the webhook payload envelope from an event.
     *
     * @return array{id: string, event_key: string, occurred_at: string, data: array, meta: array}
     */
    public function buildFromEvent(string $eventKey, ?object $event = null, ?array $data = null, array $meta = []): array
    {
        $payload = $this->extractData($eventKey, $event, $data);

        return $this->wrapInEnvelope($eventKey, $payload, $meta);
    }

    /**
     * Extract data from event using webhookPayload/transformer/public-properties strategy.
     */
    protected function extractData(string $eventKey, ?object $event = null, ?array $data = null): array
    {
        // If data is explicitly provided, use it
        if ($data !== null) {
            return $data;
        }

        // If no event object, return empty data
        if ($event === null) {
            return [];
        }

        // Strategy 1: Event defines webhookPayload() method (like broadcastWith)
        if (method_exists($event, 'webhookPayload')) {
            return $event->webhookPayload();
        }

        // Strategy 2: Config provides a transformer
        $config = $this->catalog->get($eventKey);
        if (isset($config['transformer'])) {
            $transformerClass = $config['transformer'];
            if (is_subclass_of($transformerClass, WebhookPayloadTransformer::class)) {
                $transformer = app($transformerClass);

                return $transformer->transform($event);
            }
        }

        // Strategy 3: Serialize public properties (like broadcasting)
        return $this->extractPublicProperties($event);
    }

    /**
     * Extract and serialize public properties from event object.
     */
    protected function extractPublicProperties(object $event): array
    {
        $properties = get_object_vars($event);

        // If no public properties, return minimal fallback
        if (empty($properties)) {
            return [
                'event' => [
                    'class' => get_class($event),
                ],
            ];
        }

        // Normalize all property values
        $normalized = [];
        foreach ($properties as $key => $value) {
            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    /**
     * Normalize a value for webhook payload (recursive, with depth guard).
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function normalizeValue($value, int $depth = 0)
    {
        // Guard against infinite recursion
        if ($depth > 5) {
            return ['class' => gettype($value)];
        }

        // Null and scalars pass through
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        // Arrays are recursively normalized
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeValue($item, $depth + 1), $value);
        }

        // DateTimeInterface -> ISO 8601 string
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        // Eloquent models and Arrayable -> toArray() then normalize
        if ($value instanceof Arrayable) {
            return $this->normalizeValue($value->toArray(), $depth + 1);
        }

        // JsonSerializable -> jsonSerialize() then normalize
        if ($value instanceof \JsonSerializable) {
            $serialized = $value->jsonSerialize();

            return $this->normalizeValue($serialized, $depth + 1);
        }

        // Unknown objects -> minimal representation
        return [
            'class' => get_class($value),
        ];
    }

    /**
     * Wrap payload in standard envelope.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     * @return array{id: string, event_key: string, occurred_at: string, data: array, meta: array}
     */
    protected function wrapInEnvelope(string $eventKey, array $data, array $meta): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'event_key' => $eventKey,
            'occurred_at' => now()->toIso8601String(),
            'data' => $data,
            'meta' => array_merge([
                'app_name' => config('app.name'),
                'environment' => config('app.env'),
            ], $meta),
        ];
    }
}

