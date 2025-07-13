<?php

namespace App\Filament\Pages\Jadwal;

use App\Models\Hari;
use App\Models\Jam;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\Jadwal;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Models\User;

class ScheduleMatrix extends Page implements HasForms
{

    public static function canAccess(): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }
    
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'Data Guru';
    protected static ?string $navigationLabel = 'Matriks Jadwal';
    protected static ?string $title = 'Matriks Jadwal Pelajaran';
    protected static string $view = 'filament.pages.jadwal.schedule-matrix';
    protected static ?int $navigationSort = 15;
    
    public ?array $data = [];

    public Collection $haris;
    public Collection $jams;
    public Collection $gurus;
    public Collection $mapels;
    public Collection $kelases;

    // Ubah ini menjadi array asosiatif untuk menyimpan jadwal per kelas
    // Format: ['kelas_id' => ['jam_id' => ['id_guru' => ..., 'id_mapel' => ...]]]
    public array $jadwalDataPerKelas = []; 

    public function mount(): void
    {
        $this->haris = Hari::orderBy('order')->get();
        $this->jams = Jam::orderBy('ke')->get();
        $this->gurus = Guru::all();
        $this->mapels = Mapel::all();
        $this->kelases = Kelas::all();

        $defaultHariId = $this->haris->first()?->id;
        // Atur default selectedKelasId ke 'all' untuk menampilkan semua kelas secara default
        $defaultKelasId = 'all'; 

        $this->data = [
            'selectedHariId' => $defaultHariId,
            'selectedKelasId' => $defaultKelasId,
        ];

        $this->form->fill($this->data);

        if ($defaultHariId) { // Hanya perlu hari, karena kelas bisa 'all'
            $this->loadSchedule();
        } else {
            if ($this->haris->isEmpty()) {
                Notification::make()
                    ->title('Data master Hari belum lengkap.')
                    ->body('Mohon lengkapi data Hari terlebih dahulu.')
                    ->warning()
                    ->send();
            }
            $this->jadwalDataPerKelas = [];
        }
    }

    public function form(Form $form): Form
    {
        // Siapkan opsi kelas, tambahkan "Semua Kelas" di awal
        $kelasOptions = $this->kelases->mapWithKeys(fn ($kelas) => [$kelas->id => $kelas->kelas . ' ' . $kelas->nama_kelas])->toArray();
        $kelasOptions = ['all' => 'Semua Kelas'] + $kelasOptions; // Tambahkan di awal

        return $form
            ->schema([
                Forms\Components\Fieldset::make('Filter Jadwal')
                    ->schema([
                        Forms\Components\Select::make('selectedHariId')
                            ->label('Hari')
                            ->options($this->haris->pluck('hari', 'id'))
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('selectedKelasId')
                            ->label('Kelas')
                            ->options($kelasOptions) // Gunakan opsi kelas yang sudah dimodifikasi
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /**
     * Livewire watcher method: Dipanggil saat $this->data['selectedHariId'] berubah.
     */
    public function updatedDataSelectedHariId(): void
    {
        $this->loadSchedule();
    }

    /**
     * Livewire watcher method: Dipanggil saat $this->data['selectedKelasId'] berubah.
     */
    public function updatedDataSelectedKelasId(): void
    {
        $this->loadSchedule();
    }

    public function loadSchedule(): void
    {
        $this->validate([
            'data.selectedHariId' => 'required|exists:tbl_hari,id',
            'data.selectedKelasId' => 'required', // Tidak lagi exists karena ada 'all'
        ]);

        $hariId = $this->data['selectedHariId'];
        $kelasId = $this->data['selectedKelasId'];

        $query = Jadwal::query()
            ->where('id_hari', $hariId);

        $targetKelases = collect();
        if ($kelasId === 'all') {
            $targetKelases = $this->kelases; // Ambil semua kelas
        } else {
            $targetKelases = $this->kelases->where('id', $kelasId); // Ambil kelas yang dipilih saja
            $query->where('id_kelas', $kelasId);
        }

        $existingJadwal = $query->get();

        $this->jadwalDataPerKelas = [];

        foreach ($targetKelases as $kelas) {
            $this->jadwalDataPerKelas[$kelas->id] = [];
            $kelasJadwal = $existingJadwal->where('id_kelas', $kelas->id)->keyBy('id_jam');

            foreach ($this->jams as $jam) {
                $this->jadwalDataPerKelas[$kelas->id][$jam->id] = [
                    'id_guru' => null,
                    'id_mapel' => null,
                    'jadwal_id' => null,
                ];

                if ($kelasJadwal->has($jam->id)) {
                    $jadwal = $kelasJadwal->get($jam->id);
                    $this->jadwalDataPerKelas[$kelas->id][$jam->id]['id_guru'] = $jadwal->id_guru;
                    $this->jadwalDataPerKelas[$kelas->id][$jam->id]['id_mapel'] = $jadwal->id_mapel;
                    $this->jadwalDataPerKelas[$kelas->id][$jam->id]['jadwal_id'] = $jadwal->id;
                }
            }
        }
        
        Notification::make()
            ->title('Jadwal berhasil dimuat.')
            ->success()
            ->send();
    }

    // Metode updateJadwal harus diubah agar hanya bekerja saat kelas spesifik dipilih
    public function updateJadwal(int $kelasId, int $jamId, string $type, int $value = null): void
    {
        // Jika "Semua Kelas" sedang dipilih, jangan izinkan pengeditan
        if ($this->data['selectedKelasId'] === 'all') {
            Notification::make()
                ->title('Tidak dapat mengedit jadwal saat mode "Semua Kelas".')
                ->body('Pilih kelas spesifik untuk melakukan perubahan jadwal.')
                ->warning()
                ->send();
            return;
        }

        if (!$this->data['selectedHariId'] || !$kelasId) { // Gunakan $kelasId dari parameter
            Notification::make()
                ->title('Pilih Hari dan Kelas terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        // Pastikan struktur data ada untuk kelas dan jam ini
        $this->jadwalDataPerKelas[$kelasId][$jamId] = array_merge(
            [
                'id_guru' => null,
                'id_mapel' => null,
                'jadwal_id' => null,
            ],
            $this->jadwalDataPerKelas[$kelasId][$jamId] ?? []
        );

        $this->jadwalDataPerKelas[$kelasId][$jamId][$type] = $value;

        $hariId = $this->data['selectedHariId'];
        $mapelId = $this->jadwalDataPerKelas[$kelasId][$jamId]['id_mapel'];
        $guruId = $this->jadwalDataPerKelas[$kelasId][$jamId]['id_guru'];
        $jadwalId = $this->jadwalDataPerKelas[$kelasId][$jamId]['jadwal_id'];

        if (is_null($mapelId) && is_null($guruId)) {
            if ($jadwalId) {
                Jadwal::destroy($jadwalId);
                $this->jadwalDataPerKelas[$kelasId][$jamId]['jadwal_id'] = null;
                Notification::make()->title('Jadwal dikosongkan.')->success()->send();
            } else {
                Notification::make()->title('Jadwal sudah kosong.')->info()->send();
            }
            return;
        }

        // --- Periksa kasus di mana salah satu kosong (tetapi tidak keduanya) ---
        if (is_null($mapelId)) {
            Notification::make()
                ->title('Lengkapi Jadwal')
                ->body('Selanjutnya silahkan pilih mata pelajaran.')
                ->warning()
                ->send();
            return;
        }

        if (is_null($guruId)) {
            Notification::make()
                ->title('Lengkapi Jadwal')
                ->body('Selanjutnya silahkan pilih guru.')
                ->warning()
                ->send();
            return;
        }

        $existingRecord = Jadwal::where('id_hari', $hariId)
                                ->where('id_jam', $jamId)
                                ->where('id_kelas', $kelasId)
                                ->first();

        try {
            if ($existingRecord) {
                $existingRecord->update([
                    'id_mapel' => $mapelId,
                    'id_guru' => $guruId,
                ]);
                $this->jadwalDataPerKelas[$kelasId][$jamId]['jadwal_id'] = $existingRecord->id;
                Notification::make()->title('Jadwal berhasil diperbarui.')->success()->send();
            } else {
                $newJadwal = Jadwal::create([
                    'id_hari' => $hariId,
                    'id_jam' => $jamId,
                    'id_kelas' => $kelasId,
                    'id_mapel' => $mapelId,
                    'id_guru' => $guruId,
                ]);
                $this->jadwalDataPerKelas[$kelasId][$jamId]['jadwal_id'] = $newJadwal->id;
                Notification::make()->title('Jadwal berhasil ditambahkan.')->success()->send();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            \Log::error('DEBUG JADWAL: QueryException - Code: ' . $errorCode . ', Message: ' . $errorMessage);

            if ($errorCode == 23000) {
                if (str_contains($errorMessage, 'jadwal_unique_per_guru_per_jam')) {
                    Notification::make()
                        ->title('Gagal menyimpan jadwal: Guru sudah mengajar.')
                        ->body('Guru ini sudah memiliki jadwal di kelas lain pada jam dan hari yang sama.')
                        ->danger()
                        ->send();
                } else if (str_contains($errorMessage, 'jadwal_unique_per_kelas_per_jam')) {
                    Notification::make()
                        ->title('Gagal menyimpan jadwal: Kelas sudah ada pelajaran.')
                        ->body('Kelas ini sudah memiliki jadwal pelajaran lain pada jam dan hari yang sama.')
                        ->danger()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Gagal menyimpan jadwal: Konflik data tidak teridentifikasi.')
                        ->body('Terdapat duplikasi data yang tidak diizinkan. Mohon cek log sistem untuk detail.')
                        ->danger()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Terjadi kesalahan database yang tidak terduga.')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';

        // Jika Anda punya beberapa role yang diizinkan:
        // return in_array(auth()->user()->role, ['admin', 'kepala_sekolah']);
    }
}