<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\GayaTabelLaporan;
use App\Models\Guru;
use App\Models\KehadiranHarianGuru;
use App\Models\Pengaturan;
use App\Models\PresensiGuru;
use App\Models\TunjanganTransaksi;
use App\Services\TunjanganGuruService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
        $this->sinkronTunjanganBulanan();
    }

    public function updatedDataDateStart()
    {
        $this->sinkronTunjanganBulanan();
        $this->dispatch('refreshTable');
    }

    public function updatedDataDateEnd()
    {
        $this->sinkronTunjanganBulanan();
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

    /** Pastikan baris tunjangan bulanan untuk bulan-bulan dalam rentang sudah terbentuk. */
    protected function sinkronTunjanganBulanan(): void
    {
        try {
            $mulai = Carbon::parse($this->data['dateStart'])->startOfMonth();
            $selesai = Carbon::parse($this->data['dateEnd'])->startOfMonth();
        } catch (\Throwable $e) {
            return;
        }

        if ($mulai->gt($selesai) || $mulai->diffInMonths($selesai) > 12) {
            return;
        }

        $service = app(TunjanganGuruService::class);

        for ($bulan = $mulai->copy(); $bulan->lte($selesai); $bulan->addMonth()) {
            $service->sinkronBulanan($bulan);
        }
    }

    protected function getTableQuery(): Builder
    {
        $this->validate([
            'data.dateStart' => 'required|date',
            'data.dateEnd' => 'required|date|after_or_equal:data.dateStart',
        ]);

        $mulai = Carbon::parse($this->data['dateStart'])->toDateString();
        $selesai = Carbon::parse($this->data['dateEnd'])->toDateString();

        $t = 'tbl_presensi_guru';
        $status = "{$t}.status_in";
        $belumBayar = "({$t}.paid IS NULL OR {$t}.paid = 0)";

        // Upah mengajar: angka yang sudah tersimpan saat presensi/izin/libur dicatat
        $presensi = PresensiGuru::query()
            ->select("{$t}.id_guru")
            ->selectRaw("SUM(CASE WHEN {$status} NOT IN ('izin','sakit','libur') AND {$t}.upah_dasar > 0 THEN 1 ELSE 0 END) as jam_mengajar")
            ->selectRaw("SUM(CASE WHEN {$status} IN ('izin','sakit') THEN 1 ELSE 0 END) as jam_izin")
            ->selectRaw("SUM(CASE WHEN {$status} = 'libur' THEN 1 ELSE 0 END) as jam_libur")
            ->selectRaw("SUM(CASE WHEN {$t}.jam_in IS NOT NULL AND {$t}.jam_out IS NULL AND COALESCE({$status}, '') <> 'tidak_diikuti' THEN 1 ELSE 0 END) as belum_keluar")
            ->selectRaw("SUM({$t}.upah_dasar) as upah_kotor")
            ->selectRaw("SUM(CASE WHEN {$status} NOT IN ('izin','sakit','libur') THEN {$t}.upah_dasar - {$t}.total_upah ELSE 0 END) as potongan_presensi")
            ->selectRaw("SUM(CASE WHEN {$status} IN ('izin','sakit') THEN {$t}.upah_dasar - {$t}.total_upah ELSE 0 END) as potongan_izin")
            ->selectRaw("SUM({$t}.total_upah) as upah_mengajar")
            ->selectRaw("SUM(CASE WHEN {$t}.paid = 1 THEN {$t}.total_upah ELSE 0 END) as upah_terbayar")
            ->selectRaw("SUM(CASE WHEN {$belumBayar} THEN {$t}.total_upah ELSE 0 END) as upah_tertunda")
            ->whereBetween("{$t}.tanggal_jadwal", [$mulai, $selesai])
            ->groupBy("{$t}.id_guru");

        // Tunjangan harian dan bulanan dari buku tunjangan
        $tunjangan = TunjanganTransaksi::query()
            ->join('tbl_jenis_tunjangan as jt', 'jt.id', '=', 'tbl_tunjangan_transaksi.id_jenis')
            ->select('tbl_tunjangan_transaksi.id_guru')
            ->selectRaw("SUM(CASE WHEN jt.tipe = 'harian' THEN tbl_tunjangan_transaksi.nominal ELSE 0 END) as tunj_harian")
            ->selectRaw("SUM(CASE WHEN jt.tipe = 'bulanan' THEN tbl_tunjangan_transaksi.nominal ELSE 0 END) as tunj_bulanan")
            ->selectRaw('SUM(CASE WHEN tbl_tunjangan_transaksi.paid = 1 THEN tbl_tunjangan_transaksi.nominal ELSE 0 END) as tunj_terbayar')
            ->selectRaw('SUM(CASE WHEN tbl_tunjangan_transaksi.paid = 0 THEN tbl_tunjangan_transaksi.nominal ELSE 0 END) as tunj_tertunda')
            ->whereBetween('tbl_tunjangan_transaksi.tanggal', [$mulai, $selesai])
            ->groupBy('tbl_tunjangan_transaksi.id_guru');

        $kehadiran = KehadiranHarianGuru::query()
            ->select('id_guru')
            ->selectRaw('COUNT(*) as hari_hadir')
            ->where('status', 'hadir')
            ->whereBetween('tanggal', [$mulai, $selesai])
            ->groupBy('id_guru');

        return Guru::query()
            ->leftJoinSub($presensi, 'p', 'p.id_guru', '=', 'tbl_guru.id')
            ->leftJoinSub($tunjangan, 't', 't.id_guru', '=', 'tbl_guru.id')
            ->leftJoinSub($kehadiran, 'h', 'h.id_guru', '=', 'tbl_guru.id')
            ->select([
                'tbl_guru.id',
                'tbl_guru.nama_guru',
                DB::raw('COALESCE(p.jam_mengajar, 0) as jam_mengajar'),
                DB::raw('COALESCE(p.jam_izin, 0) as jam_izin'),
                DB::raw('COALESCE(p.jam_libur, 0) as jam_libur'),
                DB::raw('COALESCE(p.belum_keluar, 0) as belum_keluar'),
                DB::raw('COALESCE(h.hari_hadir, 0) as hari_hadir'),
                DB::raw('COALESCE(p.upah_kotor, 0) as upah_kotor'),
                DB::raw('COALESCE(p.potongan_presensi, 0) as potongan_presensi'),
                DB::raw('COALESCE(p.potongan_izin, 0) as potongan_izin'),
                DB::raw('COALESCE(p.upah_mengajar, 0) as upah_mengajar'),
                DB::raw('COALESCE(t.tunj_harian, 0) as tunj_harian'),
                DB::raw('COALESCE(t.tunj_bulanan, 0) as tunj_bulanan'),
                DB::raw('(COALESCE(p.upah_mengajar, 0) + COALESCE(t.tunj_harian, 0) + COALESCE(t.tunj_bulanan, 0)) as total_penghasilan'),
                DB::raw('(COALESCE(p.upah_terbayar, 0) + COALESCE(t.tunj_terbayar, 0)) as terbayar'),
                DB::raw('(COALESCE(p.upah_tertunda, 0) + COALESCE(t.tunj_tertunda, 0)) as tertunda'),
            ])
            ->where(fn ($q) => $q->whereNotNull('p.id_guru')->orWhereNotNull('t.id_guru')->orWhereNotNull('h.id_guru'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getTableQuery())
            ->columns([
                static::kolomNomor(),
                static::kolomNama(),
                static::kolomAngka('jam_mengajar', 'Jam Mengajar', 'success'),
                static::kolomAngka('hari_hadir', 'Hari Hadir', 'success'),
                static::kolomAngka('belum_keluar', 'Belum Keluar', 'warning'),
                static::kolomRupiah('upah_mengajar', 'Upah Mengajar'),
                static::kolomRupiah('tunj_harian', 'Tunjangan Harian'),
                static::kolomRupiah('tunj_bulanan', 'Tunjangan Bulanan'),
                static::kolomRupiah('total_penghasilan', 'Total Penghasilan')->weight('bold'),
                static::kolomRupiah('terbayar', 'Terbayar', 'success'),
                static::kolomRupiah('tertunda', 'Tertunda', 'warning'),
                TextColumn::make('status_bayar')
                    ->label('Status')
                    ->state(fn ($record) => match (true) {
                        (int) $record->tertunda > 0 => 'Belum Dibayar',
                        (int) $record->total_penghasilan > 0 => 'Sudah Dibayar',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Belum Dibayar' => 'warning',
                        'Sudah Dibayar' => 'success',
                        default => 'gray',
                    }),
                // Rincian (disembunyikan, bisa ditampilkan lewat menu kolom)
                static::kolomAngka('jam_izin', 'Jam Izin/Sakit', 'info')->toggleable(isToggledHiddenByDefault: true),
                static::kolomAngka('jam_libur', 'Jam Libur')->toggleable(isToggledHiddenByDefault: true),
                static::kolomRupiah('upah_kotor', 'Upah Dasar')->toggleable(isToggledHiddenByDefault: true),
                static::kolomRupiah('potongan_presensi', 'Potongan Presensi', 'danger')->toggleable(isToggledHiddenByDefault: true),
                static::kolomRupiah('potongan_izin', 'Potongan Izin', 'danger')->toggleable(isToggledHiddenByDefault: true),
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
                    ->modalDescription(fn ($record) => 'Apakah Anda yakin ingin menandai penghasilan '
                        . $record->nama_guru . ' sebesar Rp ' . number_format((int) $record->tertunda, 0, ',', '.')
                        . ' (upah mengajar dan tunjangan) sebagai terbayar dan mengirim slip melalui Telegram?'
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

        $mulai = Carbon::parse($this->data['dateStart'])->toDateString();
        $selesai = Carbon::parse($this->data['dateEnd'])->toDateString();

        // Pastikan tunjangan sesuai data presensi terbaru sebelum dibayar
        $service = app(TunjanganGuruService::class);
        $this->sinkronTunjanganBulanan();
        PresensiGuru::where('id_guru', $idGuru)
            ->whereBetween('tanggal_jadwal', [$mulai, $selesai])
            ->distinct()
            ->pluck('tanggal_jadwal')
            ->each(fn ($tanggal) => $service->sinkronHarian($idGuru, $tanggal, true));

        // Baris upah mengajar yang sudah final dan belum dibayar
        $presensi = PresensiGuru::query()
            ->where('id_guru', $idGuru)
            ->whereBetween('tanggal_jadwal', [$mulai, $selesai])
            ->where(fn ($q) => $q->whereNull('paid')->orWhere('paid', 0))
            ->where('upah_dasar', '>', 0)
            ->get(['id', 'status_in', 'upah_dasar', 'total_upah']);

        // Baris tunjangan yang belum dibayar
        $transaksi = TunjanganTransaksi::with('jenis:id,nama,tipe')
            ->where('id_guru', $idGuru)
            ->whereBetween('tanggal', [$mulai, $selesai])
            ->where('paid', false)
            ->get();

        if ($presensi->isEmpty() && $transaksi->isEmpty()) {
            Notification::make()
                ->title('Tidak Ada yang Perlu Dibayar')
                ->body('Tidak ada upah atau tunjangan yang belum dibayar untuk guru ini dalam rentang waktu yang dipilih.')
                ->warning()
                ->send();
            return;
        }

        // Slip dihitung dari baris yang benar-benar dibayar sekarang
        $upahDasar = (int) $presensi->sum('upah_dasar');
        $upahBersih = (int) $presensi->sum('total_upah');
        $tunjangan = $transaksi
            ->groupBy(fn ($t) => $t->jenis->nama ?? 'Tunjangan')
            ->map(fn ($grup) => (int) $grup->sum('nominal'));

        $slip = [
            'jam_mengajar' => $presensi->reject(fn ($r) => in_array($r->status_in, ['izin', 'sakit', 'libur']))->count(),
            'jam_izin' => $presensi->whereIn('status_in', ['izin', 'sakit'])->count(),
            'jam_libur' => $presensi->where('status_in', 'libur')->count(),
            'upah_dasar' => $upahDasar,
            'potongan' => $upahDasar - $upahBersih,
            'upah_bersih' => $upahBersih,
            'tunjangan' => $tunjangan,
            'total' => $upahBersih + (int) $tunjangan->sum(),
        ];

        try {
            DB::transaction(function () use ($presensi, $transaksi, $idGuru) {
                PresensiGuru::whereIn('id', $presensi->pluck('id'))
                    ->where('id_guru', $idGuru)
                    ->update(['paid' => 1]);

                TunjanganTransaksi::whereIn('id', $transaksi->pluck('id'))
                    ->update(['paid' => true]);
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
            ->body('Upah mengajar dan tunjangan guru ini telah ditandai sebagai terbayar.')
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

        $awal = Carbon::parse($this->data['dateStart']);
        $akhir = Carbon::parse($this->data['dateEnd']);
        $periode = $awal->isSameMonth($akhir)
            ? $awal->translatedFormat('F Y')
            : $awal->translatedFormat('d M Y') . ' - ' . $akhir->translatedFormat('d M Y');

        $pesan = "Slip gaji periode *{$periode}*.\n";
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
        $pesan .= "Upah mengajar: *" . $rupiah($slip['upah_bersih']) . "*\n";
        foreach ($slip['tunjangan'] as $nama => $nominal) {
            $pesan .= "{$nama}: *" . $rupiah($nominal) . "*\n";
        }
        $pesan .= "Total penghasilan: *" . $rupiah($slip['total']) . "*";

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