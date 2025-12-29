<?php

namespace Pyle\Webhooks;

use Pyle\Webhooks\Contracts\WebhookSubscribableEvent;

class EventCatalog
{
    /**
     * Get all configured events.
     *
     * @return array<string, array{event: class-string, group?: string, label?: string, description?: string, transformer?: class-string}>
     */
    public function all(): array
    {
        return config('webhooks.events', []);
    }

    /**
     * Get event configuration by key.
     *
     * @return array{event: class-string, group?: string, label?: string, description?: string, transformer?: class-string}|null
     */
    public function get(string $eventKey): ?array
    {
        return $this->all()[$eventKey] ?? null;
    }

    /**
     * Get the event class for a given event key.
     *
     * @return class-string|null
     */
    public function getEventClass(string $eventKey): ?string
    {
        $config = $this->get($eventKey);

        return $config['event'] ?? null;
    }

    /**
     * Get the event key for a given event object.
     */
    public function getEventKey(object $event): ?string
    {
        // First, check if the event implements the contract
        if ($event instanceof WebhookSubscribableEvent) {
            return $event::webhookEventKey();
        }

        // Then, search config for matching event class
        $eventClass = get_class($event);

        foreach ($this->all() as $key => $config) {
            if (($config['event'] ?? null) === $eventClass) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Get metadata for an event key (for UI display).
     *
     * @return array{group: string, label: string, description: string|null}
     */
    public function getMetadata(string $eventKey): array
    {
        $config = $this->get($eventKey);

        if ($config === null) {
            return [
                'group' => 'Other',
                'label' => $eventKey,
                'description' => null,
            ];
        }

        // If event class implements contract, prefer contract metadata
        $eventClass = $config['event'] ?? null;
        if ($eventClass && is_subclass_of($eventClass, WebhookSubscribableEvent::class)) {
            return [
                'group' => $eventClass::webhookEventGroup(),
                'label' => $eventClass::webhookEventLabel(),
                'description' => $eventClass::webhookEventDescription(),
            ];
        }

        // Otherwise use config metadata
        return [
            'group' => $config['group'] ?? 'Other',
            'label' => $config['label'] ?? $eventKey,
            'description' => $config['description'] ?? null,
        ];
    }

    /**
     * Check if an event key is configured.
     */
    public function has(string $eventKey): bool
    {
        return isset($this->all()[$eventKey]);
    }
}
