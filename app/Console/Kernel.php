<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        \App\Console\Commands\GenerateGuruPresensiData::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly(); // Contoh bawaan Laravel, bisa dihapus
        $schedule->command('presensi:tutup-siswa-masuk')->dailyAt('10:00'); // Tutup presensi siswa masuk setiap hari jam 10:00
        $schedule->command('presensi:tutup-siswa-keluar')->dailyAt('23:59'); // Tutup presensi siswa keluar setiap hari jam 23:59
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}