<?php

namespace App\Console\Commands;

use App\Models\PresensiGuru;
use App\Services\HonorGuruService;
use Illuminate\Console\Command;

class HitungUlangHonorGuru extends Command
{
    protected $signature = 'presensi:hitung-ulang-honor {tanggal? : Format Y-m-d, default hari ini}';
    protected $description = 'Hitung ulang status, potongan, dan upah presensi guru pada satu tanggal';

    public function handle(HonorGuruService $service): int
    {
        $tanggal = $this->argument('tanggal') ?? now()->toDateString();

        $ids = PresensiGuru::whereDate('tanggal_jadwal', $tanggal)
            ->whereNotNull('jam_in')
            ->distinct()
            ->pluck('id_guru');

        foreach ($ids as $idGuru) {
            $service->hitungUlangHari($idGuru, $tanggal);
        }

        $this->info("Selesai: {$ids->count()} guru dihitung ulang untuk {$tanggal}.");

        return self::SUCCESS;
    }
}