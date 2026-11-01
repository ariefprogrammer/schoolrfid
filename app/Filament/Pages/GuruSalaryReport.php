<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\GayaTabelLaporan;
use App\Models\Guru;
use App\Models\Pengaturan;
use App\Models\PresensiGuru;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Filament\Tables\Columns\TextColumn;

class GuruSalaryReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, GayaTabelLaporan;

    public static function canAccess(array $parameters = []): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Laporan Gaji Guru';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 25;
    protected static ?string $title = 'Laporan Gaji Guru';
    protected static string $view = 'filament.pages.guru-salary-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data['dateStart'] = Carbon::now()->startOfMonth()->toDateString();
        $this->data['dateEnd'] = Carbon::now()->endOfMonth()->toDateString();
        $this->form->fill($this->data);
    }

    public function updatedDataDateStart()
    {
        $this->dispatch('refreshTable');
    }

    public function updatedDataDateEnd()
    {
        $this->dispatch('refreshTable');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Filter Laporan')
                    ->schema([
                        Forms\Components\DatePicker::make('dateStart')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->live(onBlur: true),
                        Forms\Components\DatePicker::make('dateEnd')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->afterOrEqual('dateStart')
                            ->live(onBlur: true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->live();
    }

    protected function getTableQuery(): Builder
    {
        $this->validate([
            'data.dateStart' => 'required|date',
            'data.dateEnd' => 'required|date|after_or_equal:data.dateStart',
        ]);

        $t = 'tbl_presensi_guru';
        $status = "{$t}.status_in";
        $belumBayar = "({$t}.paid IS NULL OR {$t}.paid = 0)";

        // Semua angka diambil dari nilai yang sudah tersimpan saat presensi/izin/libur dicatat
        return PresensiGuru::query()
            ->select(
                "{$t}.id_guru as id",
                DB::raw('MAX(tbl_guru.nama_guru) as nama_guru'),
                DB::raw("SUM(CASE WHEN {$status} NOT IN ('izin','sakit','libur') AND {$t}.upah_dasar > 0 THEN 1 ELSE 0 END) as jam_mengajar"),
                DB::raw("SUM(CASE WHEN {$status} IN ('izin','sakit') THEN 1 ELSE 0 END) as jam_izin"),
                DB::raw("SUM(CASE WHEN {$status} = 'libur' THEN 1 ELSE 0 END) as jam_libur"),
                DB::raw("SUM(CASE WHEN {$t}.jam_in IS NOT NULL AND {$t}.jam_out IS NULL AND COALESCE({$status}, '') <> 'tidak_diikuti' THEN 1 ELSE 0 END) as belum_keluar"),
                DB::raw("SUM({$t}.upah_dasar) as upah_kotor"),
                DB::raw("SUM(CASE WHEN {$status} NOT IN ('izin','sakit','libur') THEN {$t}.upah_dasar - {$t}.total_upah ELSE 0 END) as potongan_presensi"),
                DB::raw("SUM(CASE WHEN {$status} IN ('izin','sakit') THEN {$t}.upah_dasar - {$t}.total_upah ELSE 0 END) as potongan_izin"),
                DB::raw("SUM({$t}.total_upah) as total_gaji"),
                DB::raw("SUM(CASE WHEN {$t}.paid = 1 THEN {$t}.total_upah ELSE 0 END) as terbayar"),
                DB::raw("SUM(CASE WHEN {$belumBayar} THEN {$t}.total_upah ELSE 0 END) as tertunda")
            )
            ->leftJoin('tbl_guru', "{$t}.id_guru", '=', 'tbl_guru.id')
            ->whereBetween("{$t}.tanggal_jadwal", [$this->data['dateStart'], $this->data['dateEnd']])
            ->groupBy("{$t}.id_guru");
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getTableQuery())
            ->columns([
                static::kolomNomor(),
                static::kolomNama(),
                static::kolomAngka('jam_mengajar', 'Jam Mengajar', 'success'),
                static::kolomAngka('jam_izin', 'Jam Izin/Sakit', 'info'),
                static::kolomAngka('jam_libur', 'Jam Libur'),
                static::kolomAngka('belum_keluar', 'Belum Keluar', 'warning'),
                static::kolomRupiah('upah_kotor', 'Upah Dasar'),
                static::kolomRupiah('potongan_presensi', 'Potongan Presensi', 'danger'),
                static::kolomRupiah('potongan_izin', 'Potongan Izin', 'danger'),
                static::kolomRupiah('total_gaji', 'Total Gaji')->weight('bold'),
                static::kolomRupiah('terbayar', 'Terbayar', 'success'),
                static::kolomRupiah('tertunda', 'Tertunda', 'warning'),
                TextColumn::make('status_bayar')
                    ->label('Status')
                    ->state(fn ($record) => match (true) {
                        (int) $record->tertunda > 0 => 'Belum Dibayar',
                        (int) $record->total_gaji > 0 => 'Sudah Dibayar',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Belum Dibayar' => 'warning',
                        'Sudah Dibayar' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->actions([
                Action::make('bayar')
                    ->label('Bayar & Kirim Slip')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn ($record) => (int) $record->tertunda > 0)
                    ->requiresConfirmation()
                    ->modalHeading('Bayar Gaji & Kirim Slip')
                    ->modalDescription(fn ($record) => 'Apakah Anda yakin ingin menandai gaji '
                        . $record->nama_guru . ' sebesar Rp ' . number_format((int) $record->tertunda, 0, ',', '.')
                        . ' sebagai terbayar dan mengirim slip gaji melalui Telegram?'
                        . ((int) $record->belum_keluar > 0
                            ? ' Perhatian: ada ' . (int) $record->belum_keluar . ' jam yang belum presensi keluar dan belum dihitung.'
                            : ''))
                    ->modalSubmitActionLabel('Ya, Bayar')
                    ->action(fn ($record) => $this->bayarGuru((int) $record->id)),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada data presensi guru untuk rentang tanggal yang dipilih.');
    }

    protected function bayarGuru(int $idGuru): void
    {
        $this->validate([
            'data.dateStart' => 'required|date',
            'data.dateEnd' => 'required|date|after_or_equal:data.dateStart',
        ]);

        // Baris yang sudah final (upah dasar terisi) dan belum dibayar
        $rows = PresensiGuru::query()
            ->where('id_guru', $idGuru)
            ->whereBetween('tanggal_jadwal', [$this->data['dateStart'], $this->data['dateEnd']])
            ->where(fn ($q) => $q->whereNull('paid')->orWhere('paid', 0))
            ->where('upah_dasar', '>', 0)
            ->get(['id', 'status_in', 'upah_dasar', 'total_upah']);

        if ($rows->isEmpty()) {
            Notification::make()
                ->title('Tidak Ada Presensi yang Belum Dibayar')
                ->body('Tidak ada presensi yang perlu dibayar untuk guru ini dalam rentang waktu yang dipilih.')
                ->warning()
                ->send();
            return;
        }

        // Slip dihitung dari baris yang benar-benar dibayar sekarang
        $upahDasar = (int) $rows->sum('upah_dasar');
        $total = (int) $rows->sum('total_upah');
        $slip = [
            'jam_mengajar' => $rows->reject(fn ($r) => in_array($r->status_in, ['izin', 'sakit', 'libur']))->count(),
            'jam_izin' => $rows->whereIn('status_in', ['izin', 'sakit'])->count(),
            'jam_libur' => $rows->where('status_in', 'libur')->count(),
            'upah_dasar' => $upahDasar,
            'potongan' => $upahDasar - $total,
            'total' => $total,
        ];

        try {
            DB::transaction(function () use ($rows, $idGuru) {
                PresensiGuru::whereIn('id', $rows->pluck('id'))
                    ->where('id_guru', $idGuru)
                    ->update(['paid' => 1]);
            });
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Membayar Gaji')
                ->body('Terjadi kesalahan saat menandai gaji sebagai terbayar: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

        Notification::make()
            ->title('Gaji Berhasil Dibayar')
            ->body('Gaji untuk guru ini telah berhasil ditandai sebagai terbayar.')
            ->success()
            ->send();

        $this->kirimSlipTelegram($idGuru, $slip);
    }

    private function kirimSlipTelegram(int $idGuru, array $slip): void
    {
        $guru = Guru::find($idGuru);

        if (!$guru || empty($guru->telegram_chat_id)) {
            Notification::make()
                ->title('Gagal Mengirim Telegram')
                ->body('Chat ID Telegram untuk guru ' . ($guru->nama_guru ?? '-') . ' tidak ditemukan atau kosong.')
                ->warning()
                ->send();
            return;
        }

        // Token dari Pengaturan Aplikasi; .env sebagai cadangan
        $botToken = Pengaturan::first()?->token_telegram ?: env('TELEGRAM_BOT_TOKEN');

        if (empty($botToken)) {
            Notification::make()
                ->title('Gagal Mengirim Telegram')
                ->body('Token Bot Telegram belum diatur di Pengaturan Aplikasi maupun file .env.')
                ->danger()
                ->send();
            return;
        }

        $rupiah = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
        $periode = Carbon::parse($this->data['dateStart'])->translatedFormat('F Y');

        $pesan = "Slip gaji bulan *{$periode}*.\n";
        $pesan .= "Nama: *{$guru->nama_guru}*\n";
        $pesan .= "Jam mengajar: *{$slip['jam_mengajar']}*\n";
        if ($slip['jam_izin'] > 0) {
            $pesan .= "Jam izin/sakit: *{$slip['jam_izin']}*\n";
        }
        if ($slip['jam_libur'] > 0) {
            $pesan .= "Jam libur: *{$slip['jam_libur']}*\n";
        }
        $pesan .= "Upah dasar: *" . $rupiah($slip['upah_dasar']) . "*\n";
        if ($slip['potongan'] > 0) {
            $pesan .= "Potongan: *" . $rupiah($slip['potongan']) . "*\n";
        }
        $pesan .= "Total gaji: *" . $rupiah($slip['total']) . "*";

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $guru->telegram_chat_id,
                'text' => $pesan,
                'parse_mode' => 'Markdown',
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