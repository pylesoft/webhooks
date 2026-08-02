<?php

namespace Pyle\Webhooks\Livewire;

use Flux\FluxManager;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Pyle\Webhooks\Facades\Webhooks;
use Pyle\Webhooks\Models\WebhookEndpoint;

class WebhooksPage extends Component
{
    use WithPagination;

    public bool $embedded = false;

    public int $perPage = 10;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    // Secret reveal state (endpoint ID => bool)
    public array $revealedSecrets = [];

    // Test result state
    public ?array $lastTestResult = null;

    public function prepareCreateModal(): void
    {
        $this->dispatch('prepareCreate')->to(WebhookEndpointForm::class);
    }

    #[On('open-webhook-endpoint-create')]
    public function openCreateModal(): void
    {
        $this->prepareCreateModal();
        app(FluxManager::class)->modal('webhook-endpoint')->show();
    }

    public function openEditModal(int $id): void
    {
        $this->dispatch('prepareEdit', id: $id)->to(WebhookEndpointForm::class);
        app(FluxManager::class)->modal('webhook-endpoint')->show();
    }

    #[On('endpoint-saved')]
    public function handleEndpointSaved(int $endpointId): void
    {
        $this->resetPage();
        app(FluxManager::class)->modal('webhook-endpoint')->close();
    }

    public function deleteEndpoint(int $id): void
    {
        Webhooks::endpoints()->delete($id);
    }

    public function toggleRevealSecret(int $id): void
    {
        $this->revealedSecrets[$id] = !($this->revealedSecrets[$id] ?? false);
    }

    public function testEndpoint(int $id): void
    {
        $result = Webhooks::endpoints()->test($id);

        $this->lastTestResult = array_merge([
            'endpoint_id' => $id,
        ], $result);
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
        ])->layout(config('webhooks.ui.layout', 'components.layouts.app'));
    }
}
