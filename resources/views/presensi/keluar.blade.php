<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Presensi Keluar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />

    <style>
        body {
            background-color: #f8f9fa;
        }
        .logo-rfid {
            max-width: 120px;
            margin-bottom: 20px;
        }
        .nav-link.nav-outline {
            border: 1px solid #c5c5c599;
            border-radius: 5px;
            padding: 6px 15px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .nav-link.nav-outline:hover,
        .nav-link.nav-outline.active {
            color: #93b4d4 !important;
            border-color: #ffffff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Presensi RFID</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link nav-outline me-2" href="{{ route('presensi.guru.masuk.form') }}">Guru Masuk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline me-2" href="{{ route('presensi.guru.keluar.form') }}">Guru Keluar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline me-2" href="{{ route('presensi.masuk.form') }}">Siswa Masuk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline active me-2" href="{{ route('presensi.keluar.form') }}">Siswa Keluar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline me-2" href="{{ route('presensi.tendik.masuk.form') }}">Tendik Masuk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline" href="{{ route('presensi.tendik.keluar.form') }}">Tendik Keluar</a>
                </li>
            </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-4">
            <img src="https://img.icons8.com/ios-filled/100/rfid-signal.png" alt="Logo RFID" class="logo-rfid" />
            <h3 class="mt-2">Presensi Keluar</h3>
        </div>

        {{-- Pesan Sukses atau Error --}}
        @if ($successMessage)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $successMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errorMessage)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errorMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="mb-4">
            <form action="{{ route('presensi.keluar.process') }}" method="post" class="text-center">
                @csrf
                <input type="text" name="rfid" class="form-control form-control-lg text-center" placeholder="Tempelkan Kartu RFID..." autofocus required />
            </form>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong>Presensi Keluar Hari Ini</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelPresensi" class="table table-striped table-bordered table-hover nowrap" style="width:100%">
                        <thead class="table-light text-center">
                            <tr>
                                <th>No</th>
                                <th>RFID</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Jam</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @forelse ($presensiHariIni as $absensi)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $absensi->rfid }}</td>
                                    <td>{{ $absensi->siswa->nama_siswa ?? 'N/A' }}</td>
                                    <td>{{ $absensi->kelas->kelas ?? 'N/A' }} {{ $absensi->kelas->nama_kelas ?? '' }}</td>
                                    <td>
                                        @if ($absensi->status == 'pulang')
                                            <span class="badge bg-success">{{ $absensi->status }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ $absensi->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($absensi->date_time)->format('H:i') }}</td>
                                </tr>
                            @empty
                                
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#tabelPresensi').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true, // Mengaktifkan pengurutan
                info: true, // Menampilkan informasi
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                order: [[5, 'desc']] // Mengurutkan berdasarkan kolom Jam (index 5) secara descending
            });

            // Set focus ke input RFID setelah halaman dimuat atau setelah presensi
            $('input[name="rfid"]').focus();

            // Clear input RFID setelah form disubmit (untuk alat scanner RFID)
            $('form').on('submit', function() {
                setTimeout(() => {
                    $('input[name="rfid"]').val('');
                    $('input[name="rfid"]').focus();
                }, 50); // Sedikit delay untuk memastikan form submit
            });
        });
    </script>
</body>
</html>