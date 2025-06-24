<x-filament-panels::page>
    <form wire:submit.prevent="loadSchedule">
        {{ $this->form }}
    </form>

    <div class="fi-fo-fieldset-field">
        @if ($data['selectedHariId'])
            @if ($data['selectedKelasId'] === 'all')
                <div class="flex flex-wrap gap-4 mt-4">
                    @foreach ($kelases as $kelas)
                        <div class="flex-1 min-w-[300px] border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Jadwal Kelas {{ $kelas->kelas }} {{ $kelas->nama_kelas }}
                            </h3>
                            @if (!empty($jadwalDataPerKelas[$kelas->id]))
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-6 py-3">Jam Ke</th>
                                                <th scope="col" class="px-6 py-3">Waktu</th>
                                                <th scope="col" class="px-6 py-3">Mata Pelajaran</th>
                                                <th scope="col" class="px-6 py-3">Guru</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($jams as $jam)
                                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                        {{ $jam->ke }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        {{ \Carbon\Carbon::parse($jam->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jam->jam_selesai)->format('H:i') }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <x-filament::input.wrapper>
                                                            <x-filament::input.select
                                                                wire:model="jadwalDataPerKelas.{{ $kelas->id }}.{{ $jam->id }}.id_mapel"
                                                                wire:change="updateJadwal({{ $kelas->id }}, {{ $jam->id }}, 'id_mapel', $event.target.value)"
                                                                disabled
                                                            >
                                                                <option value="">-- Pilih Mapel --</option>
                                                                @foreach ($mapels as $mapel)
                                                                    {{-- UBAH INI: $mapel->mapel menjadi $mapel->mata_pelajaran --}}
                                                                    <option value="{{ $mapel->id }}">{{ $mapel->mata_pelajaran }}</option>
                                                                @endforeach
                                                            </x-filament::input.select>
                                                        </x-filament::input.wrapper>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <x-filament::input.wrapper>
                                                            <x-filament::input.select
                                                                wire:model="jadwalDataPerKelas.{{ $kelas->id }}.{{ $jam->id }}.id_guru"
                                                                wire:change="updateJadwal({{ $kelas->id }}, {{ $jam->id }}, 'id_guru', $event.target.value)"
                                                                disabled
                                                            >
                                                                <option value="">-- Pilih Guru --</option>
                                                                @foreach ($gurus as $guru)
                                                                    <option value="{{ $guru->id }}">{{ $guru->nama_guru }}</option>
                                                                @endforeach
                                                            </x-filament::input.select>
                                                        </x-filament::input.wrapper>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-gray-600 dark:text-gray-400">Tidak ada jadwal untuk kelas ini pada hari yang dipilih.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Tampilan saat kelas spesifik dipilih (logika yang sudah ada) --}}
                @php
                    $selectedKelas = $kelases->where('id', $data['selectedKelasId'])->first();
                @endphp

                @if ($selectedKelas && !empty($jadwalDataPerKelas[$selectedKelas->id]))
                    <div class="overflow-x-auto mt-4">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Jam Ke</th>
                                    <th scope="col" class="px-6 py-3">Waktu</th>
                                    <th scope="col" class="px-6 py-3">Mata Pelajaran</th>
                                    <th scope="col" class="px-6 py-3">Guru</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($jams as $jam)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ $jam->ke }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ \Carbon\Carbon::parse($jam->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jam->jam_selesai)->format('H:i') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-filament::input.wrapper>
                                                <x-filament::input.select
                                                    wire:model="jadwalDataPerKelas.{{ $selectedKelas->id }}.{{ $jam->id }}.id_mapel"
                                                    wire:change="updateJadwal({{ $selectedKelas->id }}, {{ $jam->id }}, 'id_mapel', $event.target.value)"
                                                >
                                                    <option value="">-- Pilih Mapel --</option>
                                                    @foreach ($mapels as $mapel)
                                                        {{-- UBAH INI: $mapel->mapel menjadi $mapel->mata_pelajaran --}}
                                                        <option value="{{ $mapel->id }}">{{ $mapel->mata_pelajaran }}</option>
                                                    @endforeach
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-filament::input.wrapper>
                                                <x-filament::input.select
                                                    wire:model="jadwalDataPerKelas.{{ $selectedKelas->id }}.{{ $jam->id }}.id_guru"
                                                    wire:change="updateJadwal({{ $selectedKelas->id }}, {{ $jam->id }}, 'id_guru', $event.target.value)"
                                                >
                                                    <option value="">-- Pilih Guru --</option>
                                                    @foreach ($gurus as $guru)
                                                        <option value="{{ $guru->id }}">{{ $guru->nama_guru }}</option>
                                                    @endforeach
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-4 text-gray-600 dark:text-gray-400">Tidak ada jadwal untuk hari dan kelas ini.</p>
                @endif
            @endif
        @else
            <p class="mt-4 text-gray-600 dark:text-gray-400">Silakan pilih hari untuk melihat jadwal.</p>
        @endif
    </div>
</x-filament-panels::page>