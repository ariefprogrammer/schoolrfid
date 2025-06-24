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

    use InteractsWithForms, InteractsWithTable;
    
    protected static string $resource = PresensiGuruResource::class;
    protected static string $view = 'filament.resources.absensi-resource.pages.absensi.laporan-presensi-guru';
    protected static ?string $title = 'Laporan Rekap Presensi Guru';
    protected static ?int $navigationSort = 13;
    
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

        $upahPerjam = Pengaturan::first()?->upah_perjam ?? 0;

        $query = PresensiGuru::query()
            ->select(
                // --- PENTING: ALIAS id_guru sebagai 'id' ---
                'tbl_presensi_guru.id_guru as id', // Aliaskan id_guru sebagai 'id'
                DB::raw('MAX(tbl_guru.nama_guru) as nama_guru'),
                DB::raw('COUNT(*) as target'),
                DB::raw('SUM(CASE WHEN tbl_presensi_guru.status_in = "hadir" THEN 1 ELSE 0 END) as hadir'),
                DB::raw('SUM(CASE WHEN tbl_presensi_guru.status_in = "terlambat" THEN 1 ELSE 0 END) as terlambat'),
                DB::raw('SUM(CASE WHEN tbl_presensi_guru.status_out = "pulang" THEN 1 ELSE 0 END) as pulang'),
                DB::raw('SUM(CASE WHEN tbl_presensi_guru.status_out = "bolos" THEN 1 ELSE 0 END) as bolos'),
                DB::raw('SUM(CASE WHEN tbl_presensi_guru.jam_in IS NULL AND tbl_presensi_guru.status_in = "unassign" THEN 1 ELSE 0 END) as alpa')
            )
            ->leftJoin('tbl_guru', 'tbl_presensi_guru.id_guru', '=', 'tbl_guru.id')
            ->whereBetween('tbl_presensi_guru.tanggal_jadwal', [$this->data['dateStart'], $this->data['dateEnd']])
            ->groupBy('tbl_presensi_guru.id_guru');

        if ($this->forceTableRefresh) {
            // Ini hanya untuk memastikan Livewire mendeteksi perubahan state
            // tidak melakukan apapun secara fungsional pada query
        }
        return $query;
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
                TextColumn::make('nama_guru')->label('Nama Guru')->sortable()->searchable(),
                TextColumn::make('hadir')->label('Hadir')->sortable(),
                TextColumn::make('terlambat')->label('Terlambat')->sortable(),
                TextColumn::make('pulang')->label('Pulang')->sortable(),
                TextColumn::make('bolos')->label('Bolos')->sortable(),
                TextColumn::make('alpa')->label('Alpa')->sortable(),
                TextColumn::make('target')->label('Target')->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}