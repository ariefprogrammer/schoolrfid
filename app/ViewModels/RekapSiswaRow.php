<?php

namespace App\ViewModels;

class RekapSiswaRow
{
    public function __construct(
        public string $rfid,
        public string $nama,
        public string $kelas,
        public int $hadir = 0,
        public int $terlambat = 0,
        public int $alpa = 0,
        public int $pulang = 0,
        public int $bolos = 0,
        public int $izin = 0,
        public int $sakit = 0,
    ) {}
}
