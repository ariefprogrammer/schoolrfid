<?php

namespace App\Imports;

use App\Models\Siswa;
use App\Models\Kelas;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SiswasImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Siswa([
            'rfid'           => $row['rfid'],
            'nis'            => $row['nis'],
            'nama_siswa'     => $row['nama_siswa'],
            'id_kelas'       => Kelas::where('nama_kelas', $row['kelas'])->value('id'),
            'telepon_siswa'  => $row['telepon_siswa'],
            'nama_wali'      => $row['nama_wali'],
            'telepon_wali'   => $row['telepon_wali'],
        ]);
    }

    public function rules(): array
    {
        return [
            // Pastikan NIS unik di tabel 'siswas'
            'nis' => [
                'required',
                'unique:tbl_siswa,nis',
            ],
            // Pastikan RFID unik di tabel 'siswas' (aktifkan ini!)
            'rfid' => [
                'nullable', // RFID boleh kosong
                'unique:tbl_siswa,rfid', // RFID harus unik jika diisi
            ],
            'nama_siswa' => 'required',
            // Pastikan kolom 'kelas' dari Excel ada dan nilainya sesuai dengan 'nama_kelas' di tabel 'kelas'
            'kelas' => 'required|exists:tbl_kelas,nama_kelas',
            // Anda bisa menambahkan validasi untuk kolom lain di sini jika diperlukan
        ];
    }

    public function onError(Throwable $error)
    {
        \Log::error("Import Siswa Error (onError callback): " . $error->getMessage());
    }

    /**
     * Define chunk size for reading large Excel files.
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
