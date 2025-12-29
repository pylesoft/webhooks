<?php

namespace Pyle\Webhooks\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Pyle\Webhooks\Payload\PayloadBuilder;
use Spatie\WebhookServer\WebhookCall;

class WebhooksPage extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    // Secret reveal state (endpoint ID => bool)
    public array $revealedSecrets = [];

    // Test result state
    public ?array $lastTestResult = null;

    public function prepareCreateModal(): void
    {
        $this->dispatch('prepareCreate')->to('pyle::webhook-endpoint-form');
    }

    public function openEditModal(int $id): void
    {
        $this->dispatch('prepareEdit', id: $id)->to('pyle::webhook-endpoint-form');
        $this->modal('webhook-endpoint')->show();
    }

    #[On('endpoint-saved')]
    public function handleEndpointSaved(int $endpointId): void
    {
        $this->resetPage();
        $this->modal('webhook-endpoint')->close();
    }

    public function deleteEndpoint(int $id): void
    {
        WebhookEndpoint::findOrFail($id)->delete();
    }

    public function toggleRevealSecret(int $id): void
    {
        $this->revealedSecrets[$id] = !($this->revealedSecrets[$id] ?? false);
    }

    public function testEndpoint(int $id): void
    {
        $endpoint = WebhookEndpoint::findOrFail($id);

        try {
            $payloadBuilder = app(PayloadBuilder::class);
            $payload = $payloadBuilder->buildFromEvent('webhooks.test', null, [
                'message' => 'This is a test webhook payload',
                'timestamp' => now()->toIso8601String(),
            ], [
                'test' => true,
            ]);

            WebhookCall::create()
                ->url($endpoint->url)
                ->payload($payload)
                ->useSecret($endpoint->secret)
                ->maximumTries(1)
                ->throwExceptionOnFailure()
                ->dispatchSync();

            $this->lastTestResult = [
                'endpoint_id' => $endpoint->id,
                'variant' => 'success',
                'heading' => 'Test webhook sent successfully',
                'message' => "Webhook was successfully delivered to {$endpoint->url}",
            ];
        }
        catch (\Exception $e) {
            $this->lastTestResult = [
                'endpoint_id' => $endpoint->id,
                'variant' => 'danger',
                'heading' => 'Test webhook failed',
                'message' => "Failed to deliver webhook to {$endpoint->url}: {$e->getMessage()}",
            ];
        }
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $endpoints = WebhookEndpoint::query()
            ->with('subscriptions')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('pyle-webhooks::livewire.webhooks-page', [
            'endpoints' => $endpoints,
        ]);
    }
}
