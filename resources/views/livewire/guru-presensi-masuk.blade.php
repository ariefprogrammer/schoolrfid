<div>
    <div class="container py-5">
        <div class="text-center mb-4">
            <img src="https://img.icons8.com/ios-filled/100/rfid-signal.png" alt="Logo RFID" class="logo-rfid" />
            <h3 class="mt-2">Presensi Guru Masuk</h3>
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

                        <div class="row g-3">
                            @foreach ($jadwalHariIni as $jadwal)
                                <div class="col-12">
                                    <label class="card shadow-sm border-0 bg-info text-white w-100 cursor-pointer">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="card-title fw-bold mb-1">
                                                    {{ $jadwal->mapel->mata_pelajaran ?? '-' }}
                                                </h4>
                                                <p class="mb-0">Kelas : {{ $jadwal->kelas->nama_kelas ?? '-' }}</p>
                                                <p class="mb-0">
                                                    Waktu :
                                                    {{ \Carbon\Carbon::parse($jadwal->jam->jam_mulai)->format('H:i') ?? '-' }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($jadwal->jam->jam_selesai)->format('H:i') ?? '-' }}
                                                </p>
                                            </div>
                                            <div class="form-check">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $jadwal->id }}"
                                                    wire:model.live="selectedSchedules"
                                                    class="form-check-input"
                                                >
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p>Data guru tidak ditemukan atau belum dimuat.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    {{-- --- BARU: Tombol Presensi Masuk --- --}}
                    <button type="button" class="btn btn-primary" wire:click="presensiMasuk">Presensi Masuk</button>
                    {{-- Tombol Presensi Keluar akan ditambahkan di sini nanti --}}
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

        
    </script>
</div>