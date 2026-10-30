<x-filament-panels::page>

    {{-- Filters Form --}}
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    {{-- Table --}}
    <div class="mt-8">
        {{ $this->table }}
    </div>

</x-filament-panels::page>
