<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Presensi Guru</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .logo-rfid { max-width: 120px; margin-bottom: 20px; }
        /* Tambahan untuk modal agar tampil overlay dengan benar */
        .modal.show {
            background-color: rgba(0, 0, 0, 0.5); /* Background overlay */
        }
        .card.bg-info {
            background-color:rgb(0, 60, 71) !important;
            color: #fff;
        }
        .card .form-check-input {
            transform: scale(1.5);
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .card input[type="checkbox"] {
            pointer-events: none; /* biar checkbox tidak ganggu klik label */
        }
    </style>

    @livewireStyles {{-- Penting untuk Livewire --}}
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
                <a class="nav-link nav-outline me-2" href="{{ route('presensi.masuk.form') }}">Presensi Siswa Masuk</a>
                </li>
                <li class="nav-item">
                <a class="nav-link nav-outline" href="{{ route('presensi.keluar.form') }}">Presensi Siswa Keluar</a>
                </li>
                <li class="nav-item">
                <a class="nav-link nav-outline active ms-2" href="{{ route('presensi.guru.form') }}">Presensi Guru</a>
                </li>
            </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        @livewire('guru-presensi')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @livewireScripts {{-- Penting untuk Livewire --}}
</body>
</html>