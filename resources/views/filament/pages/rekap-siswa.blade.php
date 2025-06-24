<x-filament::page>
    <form wire:submit.prevent="tampilkan" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit">
            Tampilkan
        </x-filament::button>
    </form>

    @if (!empty($rekapLaporan))
        <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 fi-ta-table">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300 fi-ta-header-cell">No</th>
                        <th class="px-4 py-3 fi-ta-header-cell">RFID</th>
                        <th class="px-4 py-3 fi-ta-header-cell">Nama</th>
                        <th class="px-4 py-3 fi-ta-header-cell">Kelas</th>
                        <th class="px-4 py-3 fi-ta-header-cell text-center">Hadir</th>
                        <th class="px-4 py-3 fi-ta-header-cell text-center">Terlambat</th>
                        <th class="px-4 py-3 fi-ta-header-cell text-center">Alpa</th>
                        <th class="px-4 py-3 fi-ta-header-cell text-center">Pulang</th>
                        <th class="px-4 py-3 fi-ta-header-cell text-center">Bolos</th>
                        <th class="px-4 py-3 fi-ta-header-cell text-center">Izin</th>
                        <th class="px-4 py-3 fi-ta-header-cell text-center">Sakit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rekapLaporan as $index => $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                            <td class="px-4 py-2 text-sm fi-ta-cell">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 text-sm fi-ta-cell">{{ $row['rfid'] }}</td>
                            <td class="px-4 py-2 text-sm fi-ta-cell">{{ $row['nama'] }}</td>
                            <td class="px-4 py-2 text-sm fi-ta-cell">{{ $row['kelas'] }}</td>
                            <td class="px-4 py-2 text-sm text-center fi-ta-cell">{{ $row['hadir'] }}</td>
                            <td class="px-4 py-2 text-sm text-center fi-ta-cell">{{ $row['terlambat'] }}</td>
                            <td class="px-4 py-2 text-sm text-center fi-ta-cell">{{ $row['alpa'] }}</td>
                            <td class="px-4 py-2 text-sm text-center fi-ta-cell">{{ $row['pulang'] }}</td>
                            <td class="px-4 py-2 text-sm text-center fi-ta-cell">{{ $row['bolos'] }}</td>
                            <td class="px-4 py-2 text-sm text-center fi-ta-cell">{{ $row['izin'] }}</td>
                            <td class="px-4 py-2 text-sm text-center fi-ta-cell">{{ $row['sakit'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex justify-end mt-4">
            <x-filament::button
                wire:click="exportExcel"
                color="success"
                class="w-auto px-4 py-2"
            >
                Export Excel
            </x-filament::button>
        </div>
    @endif
</x-filament::page>
