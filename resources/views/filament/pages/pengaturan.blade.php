<x-filament-panels::page>
    {{-- Form Pengaturan --}}
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            @if($deviceStatus === 'connect')
                <span class="inline-flex items-center gap-2 rounded-full bg-success-100 px-3 py-1 text-sm text-success-700 dark:bg-success-900 dark:text-success-300">
                    ✅ WhatsApp Terhubung ({{ $deviceNumber }})
                </span>
                <x-filament::button color="danger" size="sm" wire:click="disconnectWhatsApp">
                    Putuskan
                </x-filament::button>
            @else
                <span class="inline-flex items-center gap-2 rounded-full bg-danger-100 px-3 py-1 text-sm text-danger-700 dark:bg-danger-900 dark:text-danger-300">
                    ⚠️ WhatsApp Belum Terhubung
                </span>
                <x-filament::button size="sm" wire:click="connectWhatsApp">
                    Hubungkan WhatsApp
                </x-filament::button>
            @endif
        </div>

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />

        @if($showQrModal)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                wire:poll.3s="pollConnectionStatus"
            >
                <div class="rounded-xl bg-white p-6 text-center shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold">Scan QR dengan WhatsApp</h3>

                    @if($qrImage)
                        <img
                            src="data:image/png;base64,{{ $qrImage }}"
                            alt="QR WhatsApp"
                            class="mx-auto h-64 w-64 rounded-lg border"
                        />
                    @endif

                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Buka WhatsApp di HP → Perangkat Tertaut → Scan QR ini.
                    </p>

                    <x-filament::button color="gray" class="mt-4" wire:click="closeQrModal">
                        Batal
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament-panels::form>
</x-filament-panels::page>