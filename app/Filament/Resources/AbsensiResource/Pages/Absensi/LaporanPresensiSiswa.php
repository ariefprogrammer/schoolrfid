<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Models\Kelas;
use App\Models\Absensi;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use App\Models\User;

class LaporanPresensiSiswa extends Page implements HasForms, HasTable
{
    public static function canAccess(array $parameters = []): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }

    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = AbsensiResource::class;
    protected static string $view = 'filament.resources.absensi-resource.pages.absensi.general-report';
    protected static ?string $title = 'Laporan Presensi Siswa';
    protected static ?int $navigationSort = 22;

    public ?array $data = [];

    // --- TAMBAHKAN PROPERTI INI ---
    public $forceTableRefresh = false; // Properti penanda perubahan

    protected function getTableQuery(): Builder
    {
        // Akses data filter dari array $this->data
        $query = Absensi::with(['siswa', 'kelas'])
                        ->when(isset($this->data['date_start']), fn (Builder $query) => $query->whereDate('date_time', '>=', $this->data['date_start']))
                        ->when(isset($this->data['date_end']), fn (Builder $query) => $query->whereDate('date_time', '<=', $this->data['date_end']))
                        ->when(isset($this->data['kelas_id']), fn (Builder $query) => $query->where('id_kelas', $this->data['kelas_id']))
                        ->when(isset($this->data['jenis']), fn (Builder $query) => $query->where('jenis', $this->data['jenis']))
                        ->when(isset($this->data['status']), fn (Builder $query) => $query->where('status', $this->data['status']))
                        ->orderBy('date_time', 'desc');

        // --- TAMBAHKAN DEPENDENSI INI ---
        // Ini memastikan query dieksekusi ulang setiap kali $forceTableRefresh berubah
        if ($this->forceTableRefresh) {
            // Ini hanya untuk memastikan Livewire mendeteksi perubahan state
            // tidak melakukan apapun secara fungsional pada query
        }

        return $query;
    }

    public function mount(): void
    {
        $this->data = [
            'date_start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'date_end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'kelas_id' => null,
            'jenis' => null,
            'status' => null,
            'dummy_hidden_field' => null,
        ];
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Filter Laporan')
                    ->schema([
                        DatePicker::make('date_start')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->live(onBlur: true), // --- TAMBAHKAN .live(onBlur: true) ---
                        DatePicker::make('date_end')
                            ->label('Tanggal Akhir')
                            ->required()
                            ->live(onBlur: true), // --- TAMBAHKAN .live(onBlur: true) ---
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(
                                Kelas::all()->mapWithKeys(function ($kelas) {
                                    return [$kelas->id => $kelas->kelas . ' - ' . $kelas->nama_kelas];
                                })
                            )
                            ->searchable()
                            ->placeholder('Kelas (Opsional)')
                            ->nullable()
                            ->live(), // --- TAMBAHKAN .live() ---
                        Select::make('jenis')
                            ->label('Jenis Presensi')
                            ->options([
                                'masuk' => 'Masuk',
                                'keluar' => 'Keluar',
                                'izin' => 'Izin',
                            ])
                            ->placeholder('Pilih Jenis (Opsional)')
                            ->nullable()
                            ->live(), // --- TAMBAHKAN .live() ---
                        Select::make('status')
                            ->label('Status Presensi')
                            ->options([
                                'hadir' => 'Hadir',
                                'terlambat' => 'Terlambat',
                                'alpa' => 'Alpa',
                                'pulang' => 'Pulang',
                                'bolos' => 'Bolos',
                                'unassign' => 'Unassign'
                            ])
                            ->placeholder('Pilih Status (Opsional)')
                            ->nullable()
                            ->live(), // --- TAMBAHKAN .live() ---
                        Hidden::make('dummy_hidden_field'),
                    ])
                    ->columns(5),
            ])->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getTableQuery()
            )
            ->columns([
                TextColumn::make('date_time')
                    ->label('Tanggal & Jam')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('siswa.rfid')
                    ->label('RFID')
                    ->searchable(),
                TextColumn::make('siswa.nama_siswa')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->formatStateUsing(fn ($record) => $record->kelas->kelas . ' ' . $record->kelas->nama_kelas),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'masuk' => 'primary',
                        'keluar' => 'info',
                        'izin' => 'warning',
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'pulang' => 'success',
                        'terlambat' => 'warning',
                        'bolos' => 'danger',
                        'Izin' => 'warning',
                        default => 'secondary',
                    })
                    ->searchable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}