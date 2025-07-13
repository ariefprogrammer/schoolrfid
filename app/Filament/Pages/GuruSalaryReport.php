<?php

namespace App\Filament\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\PresensiGuru;
use App\Models\Pengaturan;
use App\Models\Guru;
use Filament\Forms\Components\Fieldset;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http; // Tambahkan ini untuk HTTP Client
use App\Models\User;

class GuruSalaryReport extends Page implements HasForms
{

    public static function canAccess(array $parameters = []): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }
    
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Laporan Gaji Guru';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 25;
    protected static ?string $title = 'Laporan Gaji Guru';
    protected static string $view = 'filament.pages.guru-salary-report';

    public ?array $data = [];
    public $startDate;
    public $endDate;
    public $reportData = [];
    public $upahPerJam = 0;

    public function mount(): void
    {
        $this->form->fill();
        $pengaturan = Pengaturan::first();
        if ($pengaturan) {
            $this->upahPerJam = $pengaturan->upah_perjam;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()->startOfMonth())
                            ->live()
                            ->columnSpan(1),
                        DatePicker::make('endDate')
                            ->label('Tanggal Akhir')
                            ->required()
                            ->default(now()->endOfMonth())
                            ->live()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $this->validate();

        $this->startDate = Carbon::parse($this->data['startDate']);
        $this->endDate = Carbon::parse($this->data['endDate']);

        // Ambil data presensi yang belum dibayar untuk guru tertentu
        // Sekarang kita ambil semua presensi untuk rentang tanggal, lalu filter di PHP
        $allPresensiInPeriod = PresensiGuru::whereBetween('tanggal_jadwal', [$this->startDate, $this->endDate])
            ->get();


        $guruIds = $allPresensiInPeriod->pluck('id_guru')->unique();
        $gurus = Guru::whereIn('id', $guruIds)->get()->keyBy('id');

        $this->reportData = [];

        foreach ($guruIds as $guruId) {
            $guru = $gurus->get($guruId);
            if (!$guru) {
                continue;
            }

            // Filter presensi hanya untuk guru ini
            $presensiForThisGuru = $allPresensiInPeriod->where('id_guru', $guruId);

            $kehadiranHadirTerlambat = $presensiForThisGuru
                ->whereIn('status_in', ['hadir', 'terlambat'])
                ->count();

            $paidCount = $presensiForThisGuru
                ->where('paid', 1)
                ->count();

            $unpaidCount = $presensiForThisGuru
                ->where('paid', 0)
                ->whereIn('status_in', ['hadir', 'terlambat'])
                ->count();

            $totalGaji = $kehadiranHadirTerlambat * $this->upahPerJam;
            $terbayar = $paidCount * $this->upahPerJam;
            $tertunda = $unpaidCount * $this->upahPerJam;

            $unpaidPresensiIds = $presensiForThisGuru
                ->where('paid', 0)
                ->whereIn('status_in', ['hadir', 'terlambat'])
                ->pluck('id')
                ->toArray();
            
            $this->reportData[] = [
                'id_guru' => $guru->id, // Tambahkan id_guru untuk keperluan pengiriman Telegram
                'nama_guru' => $guru->nama_guru,
                'kehadiran' => $kehadiranHadirTerlambat,
                'upah' => $this->upahPerJam,
                'total_gaji' => $totalGaji,
                'terbayar' => $terbayar,
                'tertunda' => $tertunda,
                'unpaid_presensi_ids' => $unpaidPresensiIds,
                'has_unpaid' => $unpaidCount > 0,
            ];
        }
    }

    public function markAsPaid(int $guruId, array $presensiIds, array $reportItemData): void
    {
        if (empty($presensiIds)) {
            Notification::make()
                ->title('Tidak Ada Presensi yang Belum Dibayar')
                ->body('Tidak ada presensi yang perlu dibayar untuk guru ini dalam rentang waktu yang dipilih.')
                ->warning()
                ->send();
            return;
        }

        try {
            DB::beginTransaction();

            PresensiGuru::whereIn('id', $presensiIds)
                        ->where('id_guru', $guruId)
                        ->update(['paid' => 1]);

            DB::commit();

            Notification::make()
                ->title('Gaji Berhasil Dibayar')
                ->body('Gaji untuk guru ini telah berhasil ditandai sebagai terbayar.')
                ->success()
                ->send();

            // Panggil fungsi pengirim Telegram setelah pembayaran berhasil
            $this->sendTelegramMessage($guruId, $reportItemData);

            // Refresh laporan setelah pembayaran
            $this->generateReport();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal Membayar Gaji')
                ->body('Terjadi kesalahan saat mencoba menandai gaji sebagai terbayar: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Metode baru untuk mengirim pesan Telegram
    private function sendTelegramMessage(int $guruId, array $reportItemData): void
    {
        $guru = Guru::find($guruId);

        if (!$guru || empty($guru->telegram_chat_id)) {
            Notification::make()
                ->title('Gagal Mengirim Telegram')
                ->body("Chat ID Telegram untuk guru {$guru->nama_guru} tidak ditemukan atau kosong.")
                ->warning()
                ->send();
            return;
        }

        $namaBulan = Carbon::parse($this->startDate)->translatedFormat('F Y'); // Menggunakan nama bulan dan tahun dari startDate

        $message = "Slip gaji bulan *$namaBulan*.\n";
        $message .= "Nama: *{$guru->nama_guru}*\n";
        $message .= "Kehadiran: *{$reportItemData['kehadiran']}*\n";
        $message .= "Upah per jam: *Rp " . number_format($reportItemData['upah'], 0, ',', '.') . "*\n";
        $message .= "Total gaji: *Rp " . number_format($reportItemData['total_gaji'], 0, ',', '.') . "*";

        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = $guru->telegram_chat_id;

        if (empty($botToken)) {
            Notification::make()
                ->title('Gagal Mengirim Telegram')
                ->body('Token Bot Telegram belum diatur di file .env.')
                ->danger()
                ->send();
            return;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown', // Menggunakan Markdown untuk bold
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->title('Telegram Terkirim')
                    ->body("Slip gaji berhasil dikirim ke {$guru->nama_guru} melalui Telegram.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Gagal Mengirim Telegram')
                    ->body("Gagal mengirim slip gaji ke {$guru->nama_guru}. Respon: " . $response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Mengirim Telegram')
                ->body('Terjadi kesalahan saat mengirim pesan Telegram: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}