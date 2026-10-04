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
use Illuminate\Support\Facades\Log;

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
        $this->jams = collect();
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
        $this->jams = Hari::findOrFail($hariId)->jamPelajaran();

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

    /**
     * Dipanggil dari view saat guru/mapel pada satu sel (kelas x jam) diubah.
     */
    public function updateJadwal(int $kelasId, int $jamId, string $type, ?int $value = null): void
    {
        // 1) Mode "Semua Kelas" hanya untuk melihat, bukan mengedit
        if (($this->data['selectedKelasId'] ?? null) === 'all') {
            Notification::make()
                ->title('Tidak dapat mengedit jadwal saat mode "Semua Kelas".')
                ->body('Pilih kelas spesifik untuk melakukan perubahan jadwal.')
                ->warning()
                ->send();
            return;
        }

        $hariId = $this->data['selectedHariId'] ?? null;

        if (! $hariId || ! $kelasId) {
            Notification::make()
                ->title('Pilih Hari dan Kelas terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        // 2) Validasi input. Method Livewire bisa dipanggil dengan argumen sembarang dari browser.
        if (! in_array($type, ['id_guru', 'id_mapel'], true)) {
            Notification::make()->title('Jenis data tidak valid.')->danger()->send();
            return;
        }

        // Jam harus milik pola jam hari yang sedang dipilih (Reguler/Jumat/dll)
        if (! $this->jams->contains('id', $jamId)) {
            Notification::make()
                ->title('Jam tidak valid untuk hari ini.')
                ->body('Muat ulang halaman lalu coba lagi.')
                ->danger()
                ->send();
            return;
        }

        // Kelas harus yang sedang ditampilkan
        if (! array_key_exists($kelasId, $this->jadwalDataPerKelas)) {
            Notification::make()->title('Kelas tidak valid.')->danger()->send();
            return;
        }

        // 3) Pastikan struktur data sel ada, lalu isi nilai baru
        $this->jadwalDataPerKelas[$kelasId][$jamId] = array_merge(
            [
                'id_guru' => null,
                'id_mapel' => null,
                'jadwal_id' => null,
            ],
            $this->jadwalDataPerKelas[$kelasId][$jamId] ?? []
        );

        $this->jadwalDataPerKelas[$kelasId][$jamId][$type] = $value;

        $mapelId = $this->jadwalDataPerKelas[$kelasId][$jamId]['id_mapel'];
        $guruId  = $this->jadwalDataPerKelas[$kelasId][$jamId]['id_guru'];

        // 4) Keduanya kosong -> hapus jadwal (kalau ada)
        if (is_null($mapelId) && is_null($guruId)) {
            $deleted = Jadwal::where('id_hari', $hariId)
                ->where('id_jam', $jamId)
                ->where('id_kelas', $kelasId)
                ->delete();

            $this->jadwalDataPerKelas[$kelasId][$jamId]['jadwal_id'] = null;

            if ($deleted) {
                Notification::make()->title('Jadwal dikosongkan.')->success()->send();
            } else {
                Notification::make()->title('Jadwal sudah kosong.')->info()->send();
            }
            return;
        }

        // 5) Salah satu masih kosong -> minta dilengkapi, belum disimpan
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

        // 6) Simpan (update atau buat baru)
        try {
            $record = Jadwal::updateOrCreate(
                [
                    'id_hari'  => $hariId,
                    'id_jam'   => $jamId,
                    'id_kelas' => $kelasId,
                ],
                [
                    'id_mapel' => $mapelId,
                    'id_guru'  => $guruId,
                ]
            );

            $this->jadwalDataPerKelas[$kelasId][$jamId]['jadwal_id'] = $record->id;

            Notification::make()
                ->title($record->wasRecentlyCreated
                    ? 'Jadwal berhasil ditambahkan.'
                    : 'Jadwal berhasil diperbarui.')
                ->success()
                ->send();
        } catch (\Illuminate\Database\QueryException $e) {
            // Simpan gagal -> kembalikan tampilan sel ke kondisi di database
            $this->restoreCell($hariId, $kelasId, $jamId);

            $errorMessage = $e->getMessage();
            Log::error('Jadwal QueryException', [
                'code'    => $e->getCode(),
                'message' => $errorMessage,
            ]);

            if ((string) $e->getCode() === '23000') {
                if (str_contains($errorMessage, 'jadwal_unique_per_guru_per_jam')) {
                    Notification::make()
                        ->title('Gagal menyimpan jadwal: Guru sudah mengajar.')
                        ->body('Guru ini sudah memiliki jadwal di kelas lain pada jam dan hari yang sama.')
                        ->danger()
                        ->send();
                } elseif (str_contains($errorMessage, 'jadwal_unique_per_kelas_per_jam')) {
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
                // Jangan tampilkan pesan SQL mentah ke pengguna, cukup di log
                Notification::make()
                    ->title('Terjadi kesalahan database yang tidak terduga.')
                    ->body('Mohon cek log sistem untuk detail.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->restoreCell($hariId, $kelasId, $jamId);
            Log::error('Jadwal error: ' . $e->getMessage());

            Notification::make()
                ->title('Terjadi kesalahan.')
                ->body('Mohon cek log sistem untuk detail.')
                ->danger()
                ->send();
        }
    }

    /**
     * Kembalikan isi satu sel ke kondisi di database (dipakai saat simpan gagal).
     */
    protected function restoreCell(int $hariId, int $kelasId, int $jamId): void
    {
        $jadwal = Jadwal::where('id_hari', $hariId)
            ->where('id_jam', $jamId)
            ->where('id_kelas', $kelasId)
            ->first();

        $this->jadwalDataPerKelas[$kelasId][$jamId] = [
            'id_guru'   => $jadwal?->id_guru,
            'id_mapel'  => $jadwal?->id_mapel,
            'jadwal_id' => $jadwal?->id,
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';

        // Jika Anda punya beberapa role yang diizinkan:
        // return in_array(auth()->user()->role, ['admin', 'kepala_sekolah']);
    }
}