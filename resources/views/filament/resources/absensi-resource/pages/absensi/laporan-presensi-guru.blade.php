<x-filament-panels::page>
    {{-- Form Filter --}}
    {{ $this->form }}

    <div class="flex justify-end gap-x-3" style="margin-top: -20px;">
        {{-- Tombol Tampilkan --}}
        <x-filament::button type="submit">
            Tampilkan
        </x-filament::button>
    </div>

    {{-- Tabel Laporan --}}
    <div class="mt-8 relative"> 
        <div
            wire:loading                             
            wire:target="data.dateStart, data.dateEnd" 
            class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 z-10 rounded-lg"
        >
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        
        {{ $this->table }}
    </div>
</x-filament-panels::page>