<div>
    @if ($todayHariId)
        <h2 class="h4 mt-2 mb-3 text-center">Jadwal Pelajaran Hari Ini ({{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }})</h2>

        @if ($kelases->isEmpty())
            <p class="text-center text-muted">Belum ada data kelas yang terdaftar.</p>
        @else
            <div class="d-flex flex-wrap justify-content-center gap-3">
                @forelse ($kelases as $kelas)
                    <div class="card shadow-sm mb-4" style="min-width: 300px; flex: 1;">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0">Kelas {{ $kelas->kelas }} {{ $kelas->nama_kelas }}</h5>
                        </div>
                        <div class="card-body p-0">
                            {{-- Filter ini untuk memastikan ada presensi yang terisi untuk kelas ini --}}
                            @if (!empty($jadwalDataPerKelas[$kelas->id]) && collect($jadwalDataPerKelas[$kelas->id])->filter(fn($j) => $j['id_mapel'] || $j['id_guru'])->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="text-center" style="width: 25%;">Waktu</th>
                                                <th scope="col" class="text-center" style="width: 20%;">Mapel</th>
                                                <th scope="col" class="text-center" style="width: 30%;">Guru</th>
                                                <th scope="col" class="text-center" style="width: 25%;">Aksi</th> {{-- Kolom Aksi baru --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($jams as $jam)
                                                @php
                                                    $jadwalEntry = $jadwalDataPerKelas[$kelas->id][$jam->id] ?? null;
                                                    // Ambil kode mapel dan nama guru dari entry jadwalDataPerKelas yang sudah diisi
                                                    $kodeMapel = $jadwalEntry && $jadwalEntry['id_mapel'] ? $mapels->firstWhere('id', $jadwalEntry['id_mapel'])->kode : '-';
                                                    $guruName = $jadwalEntry && $jadwalEntry['id_guru'] ? $gurus->firstWhere('id', $jadwalEntry['id_guru'])->nama_guru : '-';
                                                    $presensiGuruId = $jadwalEntry['presensi_guru_id'] ?? null; // Ambil ID presensi guru
                                                @endphp
                                                <tr @if($kodeMapel == '-' && $guruName == '-') class="table-light" @endif>
                                                    <td class="text-center">{{ \Carbon\Carbon::parse($jam->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jam->jam_selesai)->format('H:i') }}</td>
                                                    <td class="text-center">{{ $kodeMapel }}</td>
                                                    <td class="text-center">{{ $guruName }}</td>
                                                    <td class="text-center">
                                                        {{-- Hanya tampilkan tombol jika ada presensiGuruId --}}
                                                        @if ($presensiGuruId)
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-info"
                                                                wire:click="showDetails({{ $presensiGuruId }})"
                                                            >
                                                                Details
                                                            </button>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="p-3 text-center text-muted">Tidak ada jadwal untuk kelas ini pada hari ini.</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted">Tidak ada kelas yang ditemukan untuk menampilkan jadwal.</p>
                @endforelse
            </div>
        @endif
    @else
        <div class="alert alert-warning text-center mt-5" role="alert">
            Hari ini tidak ditemukan dalam data master hari, atau data master hari belum lengkap.
        </div>
    @endif

    @if ($showDetailsModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailsModalLabel">Detail Presensi Guru</h5>
                        <button type="button" class="btn-close" wire:click="closeDetailsModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($selectedJadwalDetails)
                            <p><strong>Guru:</strong> {{ $selectedJadwalDetails['guru_name'] }}</p>
                            <p><strong>Mata Pelajaran:</strong> {{ $selectedJadwalDetails['mapel_kode'] }}</p>
                            <hr>
                            <p><strong>Jam Masuk:</strong> {{ $selectedJadwalDetails['jam_in'] }}</p>
                            <p><strong>Status Masuk:</strong> <span class="badge {{ $selectedJadwalDetails['status_in'] == 'Hadir' ? 'bg-success' : 'bg-warning' }}">{{ $selectedJadwalDetails['status_in'] }}</span></p>
                            <hr>
                            <p><strong>Jam Keluar:</strong> {{ $selectedJadwalDetails['jam_out'] }}</p>
                            <p><strong>Status Keluar:</strong> <span class="badge {{ $selectedJadwalDetails['status_out'] == 'Selesai' ? 'bg-success' : 'bg-secondary' }}">{{ $selectedJadwalDetails['status_out'] }}</span></p>
                            @if($selectedJadwalDetails['catatan'] != '-')
                                <p><strong>Catatan:</strong> {{ $selectedJadwalDetails['catatan'] }}</p>
                            @endif
                        @else
                            <p class="text-muted text-center">Tidak ada detail yang tersedia.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div> {{-- Backdrop modal --}}
    @endif
</div>