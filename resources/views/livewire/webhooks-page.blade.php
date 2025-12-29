<div class="w-full space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Webhooks</h2>
            <p class="mt-1 text-sm text-gray-500">Manage the endpoints that receive real-time event notifications from your application.</p>
        </div>
        <flux:modal.trigger name="webhook-endpoint">
            <flux:button variant="primary" icon="plus" wire:click="prepareCreateModal">Add endpoint</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Test Result Callout --}}
    @if($lastTestResult)
    <div x-data="{ visible: true }" x-show="visible" x-transition>
        <flux:callout variant="{{ $lastTestResult['variant'] }}" icon="{{ $lastTestResult['variant'] === 'success' ? 'check-circle' : 'x-circle' }}">
            <flux:callout.heading>{{ $lastTestResult['heading'] }}</flux:callout.heading>
            <flux:callout.text>{{ $lastTestResult['message'] }}</flux:callout.text>
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
            </x-slot>
        </flux:callout>
    </div>
    @endif

    {{-- Table --}}
    @if($endpoints->isEmpty())
    {{-- Empty State --}}
    <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
        <flux:icon icon="bolt" class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">No endpoints</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a new webhook endpoint.</p>
        <div class="mt-6">
            <flux:modal.trigger name="webhook-endpoint">
                <flux:button variant="primary" icon="plus" wire:click="prepareCreateModal">Add endpoint</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    @else
    <flux:table :paginate="$endpoints">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'url'" :direction="$sortBy === 'url' ? $sortDirection : null" wire:click="sort('url')">
                Endpoint URL
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'enabled'" :direction="$sortBy === 'enabled' ? $sortDirection : null" wire:click="sort('enabled')">
                Status
            </flux:table.column>
            <flux:table.column>
                Events
            </flux:table.column>
            <flux:table.column>
                Signing Secret
            </flux:table.column>
            <flux:table.column align="end">
                <span class="sr-only">Actions</span>
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($endpoints as $endpoint)
            <flux:table.row :key="$endpoint->id">
                <flux:table.cell variant="strong">
                    <div class="flex flex-col">
                        <span class="font-mono text-gray-900 font-medium truncate max-w-2xl">{{ $endpoint->url }}</span>
                        @if($endpoint->description)
                        <span class="text-gray-500 text-xs mt-0.5">{{ $endpoint->description }}</span>
                        @endif
                    </div>
                </flux:table.cell>
                <flux:table.cell>
                    @if($endpoint->enabled)
                    <flux:badge variant="solid" color="green">Enabled</flux:badge>
                    @else
                    <flux:badge variant="solid" color="zinc">Disabled</flux:badge>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    @php
                    $subscribedKeys = $endpoint->getSubscribedEventKeys();
                    $catalog = app(\Pyle\Webhooks\EventCatalog::class);
                    @endphp
                    @if(count($subscribedKeys) > 0)
                    <div class="flex items-center gap-1.5">
                        @php
                        $firstKey = $subscribedKeys[0];
                        $metadata = $catalog->getMetadata($firstKey);
                        @endphp
                        <flux:badge variant="outline" color="zinc">{{ $metadata['label'] }}</flux:badge>
                        @if(count($subscribedKeys) > 1)
                        <span class="text-xs text-gray-400">+{{ count($subscribedKeys) - 1 }} more</span>
                        @endif
                    </div>
                    @else
                    <span class="text-xs text-gray-400 italic">No events configured</span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <div class="flex items-center gap-2 group/secret">
                        <code class="rounded bg-gray-50 px-2 py-[2px] text-xs font-mono text-gray-500 ring-1 ring-inset ring-gray-200">
                            @if(($revealedSecrets[$endpoint->id] ?? false))
                            {{ $endpoint->secret }}
                            @else
                            {{ \Illuminate\Support\Str::mask($endpoint->secret, '*', 6) }}
                            @endif
                        </code>
                        <button wire:click="toggleRevealSecret({{ $endpoint->id }})" class="text-primary-600 hover:text-primary-700 text-xs font-medium opacity-0 group-hover/secret:opacity-100 transition-opacity">
                            {{ ($revealedSecrets[$endpoint->id] ?? false) ? 'Hide' : 'Reveal' }}
                        </button>
                    </div>
                </flux:table.cell>
                <flux:table.cell align="end">
                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" square icon="ellipsis-horizontal" />
                        <flux:menu>
                            <flux:menu.item wire:click="testEndpoint({{ $endpoint->id }})" icon="paper-airplane">Test</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item wire:click="openEditModal({{ $endpoint->id }})" icon="pencil">Edit</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item wire:click="deleteEndpoint({{ $endpoint->id }})" wire:confirm="Are you sure you want to delete this endpoint?" icon="trash" variant="danger">Delete</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    @endif

    {{-- Create/Edit Modal --}}
    <flux:modal name="webhook-endpoint" class="w-2xl max-w-full" @close="prepareCreateModal">
        <livewire:pyle::webhook-endpoint-form />
    </flux:modal>
</div>
