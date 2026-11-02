<?php

namespace App\Console\Commands;

use App\Models\KehadiranHarianGuru;
use App\Models\PresensiGuru;
use App\Services\TunjanganGuruService;
use Illuminate\Console\Command;

class SinkronTunjanganGuru extends Command
{
    protected $signature = 'tunjangan:sinkron {tanggal? : Format Y-m-d, default hari ini}';
    protected $description = 'Sinkronkan kehadiran harian dan tunjangan (harian dan bulanan) guru';

    public function handle(TunjanganGuruService $service): int
    {
        $tanggal = $this->argument('tanggal') ?? now()->toDateString();

        $ids = PresensiGuru::whereDate('tanggal_jadwal', $tanggal)
            ->pluck('id_guru')
            ->merge(KehadiranHarianGuru::where('tanggal', $tanggal)->pluck('id_guru'))
            ->unique();

        foreach ($ids as $id) {
            $service->sinkronHarian((int) $id, $tanggal, true);
        }

        $service->sinkronBulanan($tanggal);

        $this->info("Selesai: {$ids->count()} guru disinkronkan untuk {$tanggal}, tunjangan bulanan diperbarui.");

        return self::SUCCESS;
    }
}