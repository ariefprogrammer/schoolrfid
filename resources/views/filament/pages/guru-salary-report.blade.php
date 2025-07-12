<x-filament-panels::page>
    <form wire:submit.prevent="generateReport" class="filament-forms-page">
        {{ $this->form }}
        <div class="h-4"></div>
        <div class="mt-4 flex justify-end gap-x-3">
            <x-filament::button
                type="submit"
                form="generateReport"
                wire:loading.attr="disabled"
                wire:target="generateReport"
            >
                Tampilkan
            </x-filament::button>
        </div>
    </form>

    @if (!empty($reportData))
        <div class="filament-tables-wrapper mt-8" style="border-radius: 0.8rem; overflow-x: auto;">
            <table class="filament-tables-table w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">No</th>
                        <th scope="col" class="px-6 py-3">Nama Guru</th>
                        <th scope="col" class="px-6 py-3">Kehadiran</th>
                        <th scope="col" class="px-6 py-3">Upah</th>
                        <th scope="col" class="px-6 py-3">Total Gaji</th>
                        <th scope="col" class="px-6 py-3">Terbayar</th>
                        <th scope="col" class="px-6 py-3">Tertunda</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportData as $index => $data)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">{{ $data['nama_guru'] }}</td>
                            <td class="px-6 py-4">{{ $data['kehadiran'] }}</td>
                            <td class="px-6 py-4">{{ number_format($data['upah'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">{{ number_format($data['total_gaji'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">{{ number_format($data['terbayar'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">{{ number_format($data['tertunda'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                @if ($data['has_unpaid'])
                                    <x-filament::button
                                        wire:click="markAsPaid({{ $data['id_guru'] }}, {{ json_encode($data['unpaid_presensi_ids']) }}, {{ json_encode($data) }})" {{-- Meneruskan $data --}}
                                        wire:confirm="Apakah Anda yakin ingin menandai gaji ini sebagai terbayar dan mengirim slip gaji melalui Telegram?"
                                        color="success"
                                        size="sm"
                                        icon="heroicon-o-check-badge"
                                    >
                                        Bayar & Kirim Slip
                                    </x-filament::button>
                                @else
                                    Sudah Dibayar
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($startDate && $endDate)
        <div class="mt-8 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg">
            Tidak ada data presensi guru untuk rentang tanggal yang dipilih.
        </div>
    @endif
</x-filament-panels::page>