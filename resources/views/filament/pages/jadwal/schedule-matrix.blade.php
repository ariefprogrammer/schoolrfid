<x-filament-panels::page>
    <form wire:submit.prevent="loadSchedule">
        {{ $this->form }}
    </form>

    <div class="fi-fo-fieldset-field">
        @if ($data['selectedHariId'])
            @php
                $isAll = $data['selectedKelasId'] === 'all';
                $kelasTampil = $isAll
                    ? $kelases
                    : $kelases->where('id', $data['selectedKelasId']);
            @endphp

            @if ($kelasTampil->isEmpty())
                <p class="mt-4 text-gray-600 dark:text-gray-400">Tidak ada kelas untuk ditampilkan.</p>
            @else
                <div class="flex flex-wrap gap-4 mt-4">
                    @foreach ($kelasTampil as $kelas)
                        <div class="flex-1 min-w-[300px] border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                             wire:key="kelas-{{ $kelas->id }}">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Jadwal Kelas {{ $kelas->kelas }} {{ $kelas->nama_kelas }}
                            </h3>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Jam Ke</th>
                                            <th scope="col" class="px-6 py-3">Waktu</th>
                                            <th scope="col" class="px-6 py-3">Sesi Pelajaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($jams as $jam)
                                            @php
                                                $sesiList = $matrix[$kelas->id][$jam->id] ?? [];
                                                $kosong = count($sesiList) === 0;
                                                // Tombol tambah hanya muncul bila sel kosong, atau semua sesi di sel itu
                                                // bermapel paralel. Server tetap memvalidasi ulang.
                                                $bolehTambah = ! $isAll && ($kosong || collect($sesiList)->every(fn ($s) => (bool) $s->mapel?->boleh_paralel));
                                            @endphp
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                                                wire:key="sel-{{ $kelas->id }}-{{ $jam->id }}">
                                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                    {{ $jam->ke }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    {{ \Carbon\Carbon::parse($jam->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jam->jam_selesai)->format('H:i') }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    @foreach ($sesiList as $sesi)
                                                        @php $lain = $sesi->kelases->where('id', '!=', $kelas->id); @endphp
                                                        <div class="flex items-start justify-between gap-3 py-1" wire:key="sesi-{{ $kelas->id }}-{{ $sesi->id }}">
                                                            <div>
                                                                <div class="font-medium text-gray-900 dark:text-white">
                                                                    {{ $sesi->mapel?->mata_pelajaran }}
                                                                    @if ($sesi->nama_kelompok)
                                                                        <x-filament::badge size="sm" class="inline-flex ml-1">{{ $sesi->nama_kelompok }}</x-filament::badge>
                                                                    @endif
                                                                </div>
                                                                <div>{{ $sesi->guru?->nama_guru }}</div>
                                                                @if ($lain->isNotEmpty())
                                                                    <div class="text-xs">
                                                                        Gabung dengan: {{ $lain->map(fn ($k) => $k->kelas . ' ' . $k->nama_kelas)->join(', ') }}
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            @unless ($isAll)
                                                                <div class="flex gap-1 shrink-0">
                                                                    <x-filament::button size="xs" color="gray"
                                                                        wire:click="mountAction('ubahSesi', { jadwal: {{ $sesi->id }}, jam: {{ $jam->id }} })">
                                                                        Ubah
                                                                    </x-filament::button>
                                                                    <x-filament::button size="xs" color="danger"
                                                                        wire:click="mountAction('hapusSesi', { jadwal: {{ $sesi->id }}, jam: {{ $jam->id }} })">
                                                                        Hapus
                                                                    </x-filament::button>
                                                                </div>
                                                            @endunless
                                                        </div>
                                                    @endforeach

                                                    @if ($kosong && $isAll)
                                                        <span class="text-gray-400">-</span>
                                                    @endif

                                                    @if ($bolehTambah)
                                                        <div class="{{ $kosong ? '' : 'mt-2' }}">
                                                            <x-filament::button size="xs" color="primary" outlined
                                                                wire:click="mountAction('tambahSesi', { jam: {{ $jam->id }} })">
                                                                {{ $kosong ? '+ Isi jam ini' : '+ Tambah sesi paralel' }}
                                                            </x-filament::button>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <p class="mt-4 text-gray-600 dark:text-gray-400">Silakan pilih hari untuk melihat jadwal.</p>
        @endif
    </div>
    <div class="mt-6 flex justify-end">
        <x-filament::button
            color="warning"
            wire:click="mountAction('generateJadwalManual')"
        >
            Generate Jadwal Manual
        </x-filament::button>
    </div>
</x-filament-panels::page>