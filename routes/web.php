<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PresensiController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Exports\RekapSiswaExport;
use Maatwebsite\Excel\Facades\Excel;

Route::middleware('auth')->group(function () {
    Route::get('/rekap-siswa/export', function () {
        $data = session('rekap_export_data');

        if (!$data) {
            abort(404, 'Tidak ada data untuk diexport');
        }

        return Excel::download(new RekapSiswaExport($data), 'rekap_siswa.xlsx');
    })->name('rekap-siswa.export');
    
    Route::get('/presensi-masuk', [PresensiController::class, 'showPresensiMasukForm'])->name('presensi.masuk.form');
    Route::post('/presensi-masuk', [PresensiController::class, 'processPresensiMasuk'])->name('presensi.masuk.process');

    Route::get('/presensi-keluar', [PresensiController::class, 'showPresensiKeluarForm'])->name('presensi.keluar.form');
    Route::post('/presensi-keluar', [PresensiController::class, 'processPresensiKeluar'])->name('presensi.keluar.process');
    
    Route::get('/presensi-guru-masuk', [\App\Http\Controllers\PresensiController::class, 'showPresensiGuruMasukForm'])->name('presensi.guru.masuk.form');
    Route::get('/presensi-guru-keluar', [\App\Http\Controllers\PresensiController::class, 'showPresensiGuruKeluarForm'])->name('presensi.guru.keluar.form');
    
});

Route::get('/login', function () {
    return redirect('/administrator/login');
})->name('login');

Route::get('/', function () {
    return redirect('/administrator/login');
});
