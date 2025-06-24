<x-filament::page>
    <div class="space-y-6">
        <div>
            {{ $this->form }}
        </div>

        <div class="pt-4">
            <x-filament::button wire:click="submit">
                Simpan
            </x-filament::button>
        </div>
    </div>
</x-filament::page>