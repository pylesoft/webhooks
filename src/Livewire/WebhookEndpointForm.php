<?php

namespace Pyle\Webhooks\Livewire;

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

    protected EventCatalog $catalog;

    public function boot(EventCatalog $catalog): void
    {
        $this->catalog = $catalog;
    }

    /**
     * Map manager rules to Livewire field names.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $mapped = [];

        foreach (Webhooks::endpoints()->rules() as $key => $rule) {
            $livewireKey = match ($key) {
                'events' => 'selectedEventKeys',
                'events.*' => 'selectedEventKeys.*',
                default => $key,
            };

            $mapped[$livewireKey] = $rule;
        }

        return $mapped;
    }

    /**
     * Map manager messages to Livewire field names.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        $mapped = [];

        foreach (Webhooks::endpoints()->messages() as $key => $message) {
            if (str_starts_with($key, 'events.')) {
                $mapped[str_replace('events.', 'selectedEventKeys.', $key)] = $message;
            } else {
                $mapped[$key] = $message;
            }
        }

        return $mapped;
    }

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
        // Pre-validate event keys exist in catalog (provides clear base-key error for tests/UI)
        foreach ($this->selectedEventKeys as $eventKey) {
            if (!$this->catalog->has($eventKey)) {
                $this->addError('selectedEventKeys', "Event key '{$eventKey}' is not configured.");

                return;
            }
        }

        // Validate remaining fields via Livewire (rules() handles key mapping)
        $this->validate();

        // Persist via manager
        if ($this->endpointId) {
            $endpoint = Webhooks::endpoints()->update(
                endpoint: $this->endpointId,
                url: $this->url,
                events: $this->selectedEventKeys,
                description: $this->description,
                enabled: $this->enabled
            );
        } else {
            $endpoint = Webhooks::endpoints()->create(
                url: $this->url,
                events: $this->selectedEventKeys,
                description: $this->description,
                enabled: $this->enabled
            );
        }

        $this->resetForm();

        // Dispatch event to parent - parent will handle modal closing
        $this->dispatch('endpoint-saved', endpointId: $endpoint->id);
    }

    public function selectAllEvents(): void
    {
        $this->selectedEventKeys = array_keys($this->catalog->all());
    }

    public function selectGroup(string $group): void
    {
        $groupEvents = [];

        foreach ($this->catalog->all() as $eventKey => $config) {
            $metadata = $this->catalog->getMetadata($eventKey);
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
        $allEvents = $this->catalog->all();

        if (empty($this->eventSearch)) {
            return $allEvents;
        }

        $search = strtolower($this->eventSearch);
        $filtered = [];

        foreach ($allEvents as $eventKey => $config) {
            $metadata = $this->catalog->getMetadata($eventKey);
            $searchable = strtolower($eventKey . ' ' . $metadata['label'] . ' ' . ($metadata['description'] ?? '') . ' ' . $metadata['group']);

            if (str_contains($searchable, $search)) {
                $filtered[$eventKey] = $config;
            }
        }

        return $filtered;
    }

    protected function getGroupedEvents(): array
    {
        $filtered = $this->getFilteredEvents();
        $grouped = [];

        foreach ($filtered as $eventKey => $config) {
            $metadata = $this->catalog->getMetadata($eventKey);
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
