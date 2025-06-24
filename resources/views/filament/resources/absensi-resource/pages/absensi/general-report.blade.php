<x-filament-panels::page>
    <form wire:submit.prevent="$toggle('forceTableRefresh')"> 
        {{ $this->form }}

        <div class="mt-4 flex justify-end gap-x-3">
            <x-filament::button type="submit">
                Tampilkan
            </x-filament::button>
        </div>
    </form>

    {{-- Tabel Laporan --}}
    <div class="mt-8">
        {{ $this->table }}
    </div>
</x-filament-panels::page>