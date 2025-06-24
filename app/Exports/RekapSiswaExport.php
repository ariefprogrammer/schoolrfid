<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapSiswaExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function ($row, $index) {
            return [
                'No' => $index + 1,
                'RFID' => $row['rfid'],
                'Nama' => $row['nama'],
                'Kelas' => $row['kelas'],
                'Hadir' => $row['hadir'],
                'Terlambat' => $row['terlambat'],
                'Alpa' => $row['alpa'],
                'Pulang' => $row['pulang'],
                'Bolos' => $row['bolos'],
                'Izin' => $row['izin'],
                'Sakit' => $row['sakit'],
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'RFID', 'Nama', 'Kelas', 'Hadir', 'Terlambat', 'Alpa', 'Pulang', 'Bolos', 'Izin', 'Sakit'];
    }
}

