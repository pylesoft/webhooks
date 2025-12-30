<?php

namespace Pyle\Webhooks\Livewire;

use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Pyle\Webhooks\EventCatalog;
use Pyle\Webhooks\Facades\Webhooks;
use Pyle\Webhooks\Models\WebhookEndpoint;

class WebhookEndpointForm extends Component
{
    public ?int $endpointId = null;

    public string $url = '';

    public ?string $description = null;

    public bool $enabled = true;

    public array $selectedEventKeys = [];

    public string $eventSearch = '';

    #[On('prepareCreate')]
    public function prepareCreate(): void
    {
        $this->resetForm();
    }

    #[On('prepareEdit')]
    public function prepareEdit(int $id): void
    {
        $endpoint = WebhookEndpoint::with('subscriptions')->findOrFail($id);

        $this->endpointId = $id;
        $this->url = $endpoint->url;
        $this->description = $endpoint->description;
        $this->enabled = $endpoint->enabled;
        $this->selectedEventKeys = $endpoint->getSubscribedEventKeys();
        $this->eventSearch = '';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $input = [
            'url' => $this->url,
            'description' => $this->description,
            'enabled' => $this->enabled,
            'events' => $this->selectedEventKeys,
        ];

        try {
            $validated = Webhooks::endpoints()->validate($input);
        }
        catch (ValidationException $exception) {
            if ($exception->validator) {
                $errors = $exception->validator->errors();

                foreach ($errors->getMessages() as $key => $messages) {
                    if (str_starts_with($key, 'events')) {
                        foreach ($messages as $message) {
                            $errors->add('selectedEventKeys', $message);
                        }
                    }
                }
            }

            throw $exception;
        }

        $events = $validated['events'] ?? [];

        if ($this->endpointId) {
            $endpoint = Webhooks::endpoints()->update(
                endpoint: $this->endpointId,
                url: $validated['url'],
                events: $events,
                description: $validated['description'] ?? null,
                enabled: $validated['enabled'] ?? null
            );
        }
        else {
            $endpoint = Webhooks::endpoints()->create(
                url: $validated['url'],
                events: $events,
                description: $validated['description'] ?? null,
                enabled: $validated['enabled'] ?? true
            );
        }

        $this->resetForm();

        // Dispatch event to parent - parent will handle modal closing
        $this->dispatch('endpoint-saved', endpointId: $endpoint->id);
    }

    public function selectAllEvents(): void
    {
        $catalog = app(EventCatalog::class);
        $this->selectedEventKeys = array_keys($catalog->all());
    }

    public function selectGroup(string $group): void
    {
        $catalog = app(EventCatalog::class);
        $groupEvents = [];

        foreach ($catalog->all() as $eventKey => $config) {
            $metadata = $catalog->getMetadata($eventKey);
            if ($metadata['group'] === $group) {
                $groupEvents[] = $eventKey;
            }
        }

        // Add all group events that aren't already selected
        $this->selectedEventKeys = array_values(array_unique(array_merge($this->selectedEventKeys, $groupEvents)));
    }

    protected function resetForm(): void
    {
        $this->endpointId = null;
        $this->url = '';
        $this->description = null;
        $this->enabled = true;
        $this->selectedEventKeys = [];
        $this->eventSearch = '';
        $this->resetErrorBag();
    }

    protected function getFilteredEvents(): array
    {
        $catalog = app(EventCatalog::class);
        $allEvents = $catalog->all();

        if (empty($this->eventSearch)) {
            return $allEvents;
        }

        $search = strtolower($this->eventSearch);
        $filtered = [];

        foreach ($allEvents as $eventKey => $config) {
            $metadata = $catalog->getMetadata($eventKey);
            $searchable = strtolower($eventKey . ' ' . $metadata['label'] . ' ' . ($metadata['description'] ?? '') . ' ' . $metadata['group']);

            if (str_contains($searchable, $search)) {
                $filtered[$eventKey] = $config;
            }
        }

        return $filtered;
    }

    protected function getGroupedEvents(): array
    {
        $catalog = app(EventCatalog::class);
        $filtered = $this->getFilteredEvents();
        $grouped = [];

        foreach ($filtered as $eventKey => $config) {
            $metadata = $catalog->getMetadata($eventKey);
            $group = $metadata['group'];

            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][$eventKey] = [
                'config' => $config,
                'metadata' => $metadata,
            ];
        }

        ksort($grouped);

        return $grouped;
    }

    public function render()
    {
        return view('pyle-webhooks::livewire.webhook-endpoint-form', [
            'groupedEvents' => $this->getGroupedEvents(),
        ]);
    }
}
