<form wire:submit="save" class="space-y-6">
    {{-- Header --}}
    <div>
        <flux:heading size="lg">{{ $endpointId ? 'Edit endpoint' : 'Add endpoint' }}</flux:heading>
        <flux:text class="mt-2">Configure a URL to receive POST requests for specific events.</flux:text>
    </div>

    {{-- Body --}}
    <div class="space-y-6">
        {{-- URL Field --}}
        <flux:field>
            <flux:label>Endpoint URL</flux:label>
            <flux:input wire:model="url" type="url" placeholder="https://api.example.com/webhooks" required />
            <flux:description>The URL must be publicly accessible and use HTTPS.</flux:description>
            <flux:error name="url" />
        </flux:field>

        {{-- Description Field --}}
        <flux:field>
            <flux:label>Description <span class="text-gray-400 font-normal">(Optional)</span></flux:label>
            <flux:input wire:model="description" type="text" placeholder="e.g. Syncs orders to inventory system" />
            <flux:error name="description" />
        </flux:field>

        {{-- Enabled Switch --}}
        <flux:switch wire:model="enabled" label="Enabled" description="When enabled, this endpoint will receive webhook notifications for selected events." />

        {{-- Events Selector --}}
        <div class="border-t border-gray-100 pt-4 space-y-4">
            <div class="flex items-center justify-between">
                <flux:label>Events to send</flux:label>
                <flux:button type="button" wire:click="selectAllEvents" variant="ghost" size="sm">Select all events</flux:button>
            </div>

            {{-- Search --}}
            <flux:field>
                <flux:input wire:model.live.debounce.300ms="eventSearch" type="text" placeholder="Filter events..." icon="magnifying-glass" />
            </flux:field>

            {{-- Events List --}}
            @if(count($groupedEvents) > 0)
            <div class="border border-gray-200 rounded-lg overflow-hidden bg-gray-50/50 max-h-64 overflow-y-auto">
                <div class="bg-white px-4 py-2 border-b border-gray-200">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Available Events</span>
                </div>
                <div class="p-2 space-y-2">
                    @foreach($groupedEvents as $group => $events)
                    <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
                        <div class="px-3 py-2 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-700">{{ $group }}</span>
                            <flux:button type="button" wire:click="selectGroup('{{ $group }}')" variant="ghost" size="xs">Select all</flux:button>
                        </div>
                        <div class="p-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($events as $eventKey => $eventData)
                            <label class="flex items-center space-x-3 p-1.5 rounded hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="checkbox" wire:model="selectedEventKeys" value="{{ $eventKey }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600" />
                                <span class="text-sm text-gray-700 font-medium">{{ $eventData['metadata']['label'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="text-center py-8 text-sm text-gray-500">
                @if(!empty($eventSearch))
                No events match your search.
                @else
                No events are configured. Add events to your <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">config/webhooks.php</code> file.
                @endif
            </div>
            @endif
            <flux:error name="selectedEventKeys" />
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex gap-3">
        <flux:spacer />
        <flux:modal.close>
            <flux:button variant="filled" type="button">Cancel</flux:button>
        </flux:modal.close>
        <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">{{ $endpointId ? 'Update endpoint' : 'Add endpoint' }}</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</form>
