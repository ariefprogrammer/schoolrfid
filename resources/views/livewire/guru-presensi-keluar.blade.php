<div>
    <div class="container py-5">
        <div class="text-center mb-4">
            <img src="https://img.icons8.com/ios-filled/100/rfid-signal.png" alt="Logo RFID" class="logo-rfid" />
            <h3 class="mt-2">Presensi Guru Keluar</h3> {{-- <-- Perbarui Judul --}}
        </div>

        <div class="mb-4">
            <form wire:submit.prevent="showJadwal" class="text-center">
                <input
                    type="text"
                    id="rfid_input"
                    wire:model.live="rfid"
                    class="form-control form-control-lg text-center"
                    placeholder="Tempelkan Kartu RFID..."
                    autofocus
                    required
                />
                @error('rfid') <span class="text-danger">{{ $message }}</span> @enderror
                <button type="submit" class="btn btn-primary mt-3">Show Jadwal</button>
            </form>

            @if ($notificationTitle)
                <div class="alert alert-{{ $notificationType }} alert-dismissible fade show mt-3" role="alert">
                    <strong>{{ $notificationTitle }}</strong> {{ $notificationBody }}
                    <button type="button" class="btn-close" wire:click="clearNotification" aria-label="Close"></button>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade {{ $showModal ? 'show d-block' : '' }}" id="jadwalGuruModal" tabindex="-1" aria-labelledby="jadwalGuruModalLabel" aria-hidden="{{ $showModal ? 'false' : 'true' }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jadwalGuruModalLabel">Jadwal Guru: {{ $guru->nama_guru ?? '' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($guru)
                        <p><strong>NIP:</strong> {{ $guru->nip }}</p>
                        <p><strong>Email:</strong> {{ $guru->email }}</p>

                        @if ($jadwalHariIni->isNotEmpty())
                            <h6>Jadwal Aktif Hari Ini ({{ \Carbon\Carbon::now()->isoFormat('dddd') }})</h6> {{-- <-- Perbarui Judul Tabel --}}
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Jam Ke-</th>
                                            <th>Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Kelas</th>
                                            <th>Status Presensi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($jadwalHariIni as $jadwal)
                                            <tr>
                                                <td>
                                                    <input
                                                        type="checkbox"
                                                        value="{{ $jadwal->id }}"
                                                        wire:model.live="selectedSchedules"
                                                        class="form-check-input"
                                                        {{-- Nonaktifkan checkbox jika sudah presensi keluar --}}
                                                        {{ !is_null($jadwal->jam_out) ? 'disabled' : '' }}
                                                    >
                                                </td>
                                                <td>{{ $jadwal->jam->ke ?? '-' }}</td>
                                                <td>{{ \Carbon\Carbon::parse($jadwal->jam->jam_mulai)->format('H:i') ?? '-' }} - {{ \Carbon\Carbon::parse($jadwal->jam->jam_selesai)->format('H:i') ?? '-' }}</td>
                                                <td>{{ $jadwal->mapel->mata_pelajaran ?? '-' }}</td>
                                                <td>{{ ($jadwal->kelas->kelas ?? '') . ' ' . ($jadwal->kelas->nama_kelas ?? '-') }}</td>
                                                <td>
                                                    @if ($jadwal->jam_in && $jadwal->status_in)
                                                        <span class="badge bg-success">Masuk ({{ \Carbon\Carbon::parse($jadwal->jam_in)->format('H:i') }})</span>
                                                    @endif

                                                    @if ($jadwal->jam_out && $jadwal->status_out)
                                                        <span class="badge bg-info">Keluar ({{ \Carbon\Carbon::parse($jadwal->jam_out)->format('H:i') }})</span>
                                                    @elseif ($jadwal->jam_in && is_null($jadwal->jam_out))
                                                        <span class="badge bg-primary">Aktif</span> {{-- <-- Status Aktif --}}
                                                    @else
                                                        <span class="badge bg-secondary">N/A</span> {{-- Seharusnya tidak terjadi jika filter query sudah benar --}}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="alert alert-info">Tidak ada jadwal aktif untuk guru ini hari ini.</p>
                        @endif
                    @else
                        <p>Data guru tidak ditemukan atau belum dimuat.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    {{-- HAPUS TOMBOL PRESENSI MASUK DI SINI (akan ada di halaman masuk) --}}
                    <button type="button" class="btn btn-warning" wire:click="presensiKeluar">Presensi Keluar</button> {{-- <-- Tombol ini --}}
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            var myModalEl = document.getElementById('jadwalGuruModal');
            var modal = new bootstrap.Modal(myModalEl);

            @this.on('modal-toggled', (show) => {
                if (show) {
                    modal.show();
                    myModalEl.addEventListener('hidden.bs.modal', function () {
                        document.getElementById('rfid_input').focus();
                    }, { once: true });
                } else {
                    modal.hide();
                }
            });

            document.getElementById('rfid_input').focus();
        });

        Livewire.on('close-bootstrap-modal', () => {
            var myModalEl = document.getElementById('jadwalGuruModal');
            var modalInstance = bootstrap.Modal.getInstance(myModalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    </script>
</div>