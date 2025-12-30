<?php

namespace Pyle\Webhooks;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Pyle\Webhooks\Rules\EventKeyExists;

class WebhookEndpointManager
{
    public function __construct(
        protected EventCatalog $catalog
    ) {}

    /**
     * Get validation rules for endpoint creation/update.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'starts_with:https://'],
            'description' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'events' => ['array'],
            'events.*' => ['string', new EventKeyExists($this->catalog)],
        ];
    }

    /**
     * Get validation messages for endpoint rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'The URL field is required.',
            'url.url' => 'The URL must be a valid URL.',
            'url.starts_with' => 'The URL must start with https://',
            'description.max' => 'The description may not be greater than 255 characters.',
            'enabled.boolean' => 'The enabled field must be true or false.',
            'events.array' => 'The events must be an array.',
            'events.*.string' => 'Each event key must be a string.',
        ];
    }

    /**
     * Validate endpoint input data.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $input): array
    {
        return Validator::make($input, $this->rules(), $this->messages())->validate();
    }

    /**
     * Create a new webhook endpoint.
     *
     * @param  array<string>  $events
     *
     * @throws ValidationException
     */
    public function create(
        string $url,
        array $events = [],
        ?string $description = null,
        bool $enabled = true
    ): WebhookEndpoint {
        $validated = $this->validate([
            'url' => $url,
            'description' => $description,
            'enabled' => $enabled,
            'events' => $events,
        ]);

        $endpoint = WebhookEndpoint::create([
            'url' => $validated['url'],
            'description' => $validated['description'] ?? null,
            'enabled' => $validated['enabled'],
        ]);

        $this->syncSubscriptions($endpoint, $validated['events']);

        return $endpoint;
    }

    /**
     * Update an existing webhook endpoint.
     *
     * @param  array<string>|null  $events
     *
     * @throws ValidationException
     */
    public function update(
        WebhookEndpoint|int $endpoint,
        ?string $url = null,
        ?array $events = null,
        ?string $description = null,
        ?bool $enabled = null
    ): WebhookEndpoint {
        if (is_int($endpoint)) {
            $endpoint = WebhookEndpoint::findOrFail($endpoint);
        }

        $input = [];
        if ($url !== null) {
            $input['url'] = $url;
        }
        if ($description !== null) {
            $input['description'] = $description;
        }
        if ($enabled !== null) {
            $input['enabled'] = $enabled;
        }
        if ($events !== null) {
            $input['events'] = $events;
        }

        // Only validate if we have input
        if (empty($input)) {
            return $endpoint;
        }

        // Build partial validation rules for only the fields being updated
        $rules = $this->rules();
        $partialRules = [];
        foreach ($input as $key => $value) {
            if (isset($rules[$key])) {
                $partialRules[$key] = $rules[$key];
            }
            // Handle nested rules like events.*
            if ($key === 'events' && isset($rules['events.*'])) {
                $partialRules['events.*'] = $rules['events.*'];
            }
        }

        $validated = Validator::make($input, $partialRules, $this->messages())->validate();

        // Update only provided fields
        $updateData = [];
        if (isset($validated['url'])) {
            $updateData['url'] = $validated['url'];
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['enabled'])) {
            $updateData['enabled'] = $validated['enabled'];
        }

        if (!empty($updateData)) {
            $endpoint->update($updateData);
        }

        // Sync subscriptions if events were provided
        if (isset($validated['events'])) {
            $this->syncSubscriptions($endpoint, $validated['events']);
        }

        return $endpoint->fresh();
    }

    /**
     * Delete a webhook endpoint.
     */
    public function delete(WebhookEndpoint|int $endpoint): void
    {
        if (is_int($endpoint)) {
            $endpoint = WebhookEndpoint::findOrFail($endpoint);
        }

        $endpoint->subscriptions()->delete();
        $endpoint->delete();
    }

    /**
     * Test a webhook endpoint by sending a test payload.
     *
     * @return array{variant: string, heading: string, message: string}
     */
    public function test(WebhookEndpoint|int $endpoint): array
    {
        if (is_int($endpoint)) {
            $endpoint = WebhookEndpoint::findOrFail($endpoint);
        }

        try {
            $payloadBuilder = app(PayloadBuilder::class);
            $payload = $payloadBuilder->buildFromEvent('webhooks.test', null, [
                'message' => 'This is a test webhook payload',
                'timestamp' => now()->toIso8601String(),
            ], [
                'test' => true,
            ]);

            \Spatie\WebhookServer\WebhookCall::create()
                ->url($endpoint->url)
                ->payload($payload)
                ->useSecret($endpoint->secret)
                ->maximumTries(1)
                ->throwExceptionOnFailure()
                ->dispatchSync();

            return [
                'variant' => 'success',
                'heading' => 'Test webhook sent successfully',
                'message' => "Webhook was successfully delivered to {$endpoint->url}",
            ];
        }
        catch (\Exception $e) {
            return [
                'variant' => 'danger',
                'heading' => 'Test webhook failed',
                'message' => "Failed to deliver webhook to {$endpoint->url}: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Sync event subscriptions for an endpoint.
     *
     * @param  array<string>  $eventKeys
     */
    protected function syncSubscriptions(WebhookEndpoint $endpoint, array $eventKeys): void
    {
        $endpoint->subscriptions()->delete();

        foreach ($eventKeys as $eventKey) {
            $endpoint->subscriptions()->create(['event_key' => $eventKey]);
        }
    }
}

