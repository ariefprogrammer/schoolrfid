<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Presensi Guru Masuk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body { background-color: #f8f9fa; }
        .logo-rfid { max-width: 120px; margin-bottom: 20px; }
        .modal.show { background-color: rgba(0, 0, 0, 0.5); }
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

    {{-- Penting: Livewire Styles --}}
    @livewireStyles
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
                    <a class="nav-link nav-outline active me-2" href="{{ route('presensi.guru.masuk.form') }}">Guru Masuk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline me-2" href="{{ route('presensi.guru.keluar.form') }}">Guru Keluar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline me-2" href="{{ route('presensi.masuk.form') }}">Siswa Masuk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-outline me-2" href="{{ route('presensi.keluar.form') }}">Siswa Keluar</a>
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

    <div class="container mt-4">
        @livewire('guru-presensi-masuk')
        @livewire('today-schedule-matrix')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Penting: Livewire Scripts --}}
    @livewireScripts
</body>
</html>
