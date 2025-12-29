<?php

namespace Pyle\Webhooks\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Pyle\Webhooks\EventCatalog;
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
        $validated = $this->validate([
            'url' => ['required', 'url', 'starts_with:https://'],
            'description' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'selectedEventKeys' => ['array'],
            'selectedEventKeys.*' => ['string'],
        ]);

        // Validate that all selected event keys exist in catalog
        $catalog = app(EventCatalog::class);
        foreach ($validated['selectedEventKeys'] as $eventKey) {
            if (!$catalog->has($eventKey)) {
                $this->addError('selectedEventKeys', "Event key '{$eventKey}' is not configured.");

                return;
            }
        }

        if ($this->endpointId) {
            $endpoint = WebhookEndpoint::findOrFail($this->endpointId);
            $endpoint->update([
                'url' => $validated['url'],
                'description' => $validated['description'],
                'enabled' => $validated['enabled'],
            ]);
        } else {
            $endpoint = WebhookEndpoint::create([
                'url' => $validated['url'],
                'description' => $validated['description'],
                'enabled' => $validated['enabled'],
            ]);
        }

        // Sync subscriptions
        $endpoint->subscriptions()->delete();
        foreach ($validated['selectedEventKeys'] as $eventKey) {
            $endpoint->subscriptions()->create(['event_key' => $eventKey]);
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
