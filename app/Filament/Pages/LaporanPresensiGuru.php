<?php

namespace App\Filament\Resources\PresensiGuruResource\Pages;

use App\Filament\Resources\PresensiGuruResource;
use App\Models\Pengaturan;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PresensiGuru;
use App\Models\Guru;
use Filament\Forms;
use Livewire\Attributes\On;
use App\Models\User;
use App\Models\Jam;
use App\Filament\Concerns\GayaTabelLaporan;

class LaporanPresensiGuru extends Page implements HasForms, HasTable
{

    public static function canAccess(array $parameters = []): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }

    public static function getNavigationItem(): array
    {
        return [
            'visible' => auth()->user()->role === 'admin', // Hanya tampilkan jika role adalah 'admin'
        ];
    }

    use InteractsWithForms, InteractsWithTable, GayaTabelLaporan;
    
    protected static string $resource = PresensiGuruResource::class;
    protected static string $view = 'filament.resources.absensi-resource.pages.absensi.laporan-presensi-guru';
    protected static ?string $title = 'Laporan Rekap Presensi Guru';
    
    public ?array $data = [];
    
    // --- TAMBAHKAN PROPERTI INI ---
    public $forceTableRefresh = false; // Properti penanda perubahan

    public function updatedDataDateStart()
    {
        $this->refreshTable();
    }

    public function updatedDataDateEnd()
    {
        $this->refreshTable();
    }

    public function refreshTable()
    {
        $this->dispatch('refreshTable');
    }

    protected function getTableQuery(): Builder
    {
        // Lakukan validasi input filter sebelum menjalankan query
        $this->validate([
            'data.dateStart' => 'required|date',
            'data.dateEnd' => 'required|date|after_or_equal:data.dateStart',
        ]);

        $jam = (new Jam)->getTable();

        // Jam pelajaran "diikuti" bila rentang kehadiran guru bersinggungan dengan rentang jamnya.
        // Aturan ini harus sama dengan HonorGuruService::diikuti(); ubah keduanya bersamaan.
        $diikuti = "tbl_presensi_guru.jam_in IS NOT NULL
            AND tbl_presensi_guru.jam_in < TIMESTAMP(tbl_presensi_guru.tanggal_jadwal, {$jam}.jam_selesai)
            AND (tbl_presensi_guru.jam_out IS NULL
                OR tbl_presensi_guru.jam_out > TIMESTAMP(tbl_presensi_guru.tanggal_jadwal, {$jam}.jam_mulai))";

        // Kategori tiap baris; saling lepas sehingga jumlah semua kategori = target
        $kategori = "CASE
            WHEN tbl_presensi_guru.status_in = 'libur' THEN 'libur'
            WHEN tbl_presensi_guru.status_in = 'izin' THEN 'izin'
            WHEN tbl_presensi_guru.status_in = 'sakit' THEN 'sakit'
            WHEN ({$diikuti}) THEN IF(tbl_presensi_guru.status_in = 'terlambat', 'terlambat', 'hadir')
            WHEN tbl_presensi_guru.jam_in IS NOT NULL THEN 'tidak_diikuti'
            ELSE 'alpa'
        END";

        $hitung = fn (string $kondisi) => "SUM(CASE WHEN {$kondisi} THEN 1 ELSE 0 END)";

        return PresensiGuru::query()
            ->select(
                // Alias id_guru sebagai 'id' (dipakai Filament sebagai kunci baris)
                'tbl_presensi_guru.id_guru as id',
                DB::raw('MAX(tbl_guru.nama_guru) as nama_guru'),
                DB::raw('COUNT(*) as target'),
                DB::raw($hitung("({$kategori}) = 'hadir'") . ' as hadir'),
                DB::raw($hitung("({$kategori}) = 'terlambat'") . ' as terlambat'),
                DB::raw($hitung("({$kategori}) IN ('hadir','terlambat') AND tbl_presensi_guru.status_out = 'pulang'") . ' as pulang'),
                DB::raw($hitung("({$kategori}) IN ('hadir','terlambat') AND tbl_presensi_guru.status_out = 'bolos'") . ' as bolos'),
                DB::raw($hitung("({$kategori}) = 'izin'") . ' as izin'),
                DB::raw($hitung("({$kategori}) = 'sakit'") . ' as sakit'),
                DB::raw($hitung("({$kategori}) = 'libur'") . ' as libur'),
                DB::raw($hitung("({$kategori}) = 'tidak_diikuti'") . ' as tidak_diikuti'),
                DB::raw($hitung("({$kategori}) = 'alpa'") . ' as alpa'),
                DB::raw($hitung("({$kategori}) IN ('hadir','terlambat') AND tbl_presensi_guru.jam_out IS NULL") . ' as belum_keluar'),
                DB::raw("SUM(CASE WHEN ({$kategori}) = 'terlambat' THEN tbl_presensi_guru.durasi_terlambat ELSE 0 END) as menit_terlambat"),
                DB::raw("SUM(CASE WHEN ({$kategori}) IN ('hadir','terlambat') AND tbl_presensi_guru.status_out = 'bolos' THEN tbl_presensi_guru.durasi_bolos ELSE 0 END) as menit_bolos"),
                DB::raw("SUM(CASE WHEN ({$kategori}) IN ('hadir','terlambat') AND tbl_presensi_guru.jam_out IS NOT NULL THEN tbl_presensi_guru.upah_dasar - tbl_presensi_guru.total_upah ELSE 0 END) as potongan"),
                DB::raw('SUM(tbl_presensi_guru.total_upah) as honor')
            )
            ->leftJoin('tbl_guru', 'tbl_presensi_guru.id_guru', '=', 'tbl_guru.id')
            ->leftJoin($jam, "{$jam}.id", '=', 'tbl_presensi_guru.id_jam')
            ->whereBetween('tbl_presensi_guru.tanggal_jadwal', [$this->data['dateStart'], $this->data['dateEnd']])
            ->groupBy('tbl_presensi_guru.id_guru');
    }

    public function mount(): void
    {
        $this->data['dateStart'] = Carbon::now()->startOfMonth()->toDateString();
        $this->data['dateEnd'] = Carbon::now()->endOfMonth()->toDateString();
        $this->form->fill($this->data);
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


    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getTableQuery())
            ->columns([
                static::kolomNomor(),
                static::kolomNama(),
                static::kolomAngka('hadir', 'Hadir', 'success'),
                static::kolomAngka('terlambat', 'Terlambat', 'warning'),
                static::kolomAngka('pulang', 'Pulang', 'success'),
                static::kolomAngka('bolos', 'Bolos', 'danger'),
                static::kolomAngka('izin', 'Izin', 'info'),
                static::kolomAngka('sakit', 'Sakit', 'info'),
                static::kolomAngka('libur', 'Libur'),
                static::kolomAngka('tidak_diikuti', 'Tidak Diikuti', 'warning'),
                static::kolomAngka('alpa', 'Alpa', 'danger'),
                static::kolomAngka('belum_keluar', 'Belum Keluar', 'warning'),
                static::kolomAngka('menit_terlambat', 'Menit Terlambat', 'warning'),
                static::kolomAngka('menit_bolos', 'Menit Bolos', 'danger'),
                static::kolomAngka('target', 'Target', 'primary'),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada data presensi guru untuk rentang tanggal yang dipilih.');
    }
}