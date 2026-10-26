<?php

namespace App\Filament\Pages\Jadwal;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ScheduleMatrix extends Page implements HasForms
{
    use InteractsWithForms;

    public static function canAccess(): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }

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

    public function mount(): void
    {
        $this->haris = Hari::orderBy('order')->get();
        $this->jams = collect();
        $this->gurus = Guru::all();
        $this->mapels = Mapel::all();
        $this->kelases = Kelas::all();

        $defaultHariId = $this->haris->first()?->id;

        $this->data = [
            'selectedHariId' => $defaultHariId,
            'selectedKelasId' => 'all',
        ];

        $this->form->fill($this->data);

        if ($defaultHariId) {
            $this->loadSchedule();
        } elseif ($this->haris->isEmpty()) {
            Notification::make()
                ->title('Data master Hari belum lengkap.')
                ->body('Mohon lengkapi data Hari terlebih dahulu.')
                ->warning()
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        $kelasOptions = $this->kelases
            ->mapWithKeys(fn ($kelas) => [$kelas->id => $kelas->kelas . ' ' . $kelas->nama_kelas])
            ->toArray();
        $kelasOptions = ['all' => 'Semua Kelas'] + $kelasOptions;

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
                            ->options($kelasOptions)
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function updatedDataSelectedHariId(): void
    {
        $this->loadSchedule();
    }

    public function updatedDataSelectedKelasId(): void
    {
        $this->loadSchedule();
    }

    /**
     * Memuat pola jam untuk hari terpilih. Data jadwal sendiri dibangun
     * setiap render lewat getViewData() sehingga selalu sesuai isi database.
     */
    public function loadSchedule(): void
    {
        $this->validate([
            'data.selectedHariId' => 'required|exists:tbl_hari,id',
            'data.selectedKelasId' => 'required',
        ]);

        $this->jams = Hari::findOrFail($this->data['selectedHariId'])->jamPelajaran();

        Notification::make()
            ->title('Jadwal berhasil dimuat.')
            ->success()
            ->send();
    }

    /**
     * Data untuk view: $matrix[kelas_id][jam_id] = list sesi (Jadwal) pada sel tersebut.
     */
    protected function getViewData(): array
    {
        return ['matrix' => $this->buildMatrix()];
    }

    protected function buildMatrix(): array
    {
        $hariId = $this->data['selectedHariId'] ?? null;
        $kelasId = $this->data['selectedKelasId'] ?? null;

        if (! $hariId || ! $kelasId || $this->jams->isEmpty()) {
            return [];
        }

        $query = Jadwal::query()
            ->with(['mapel', 'guru', 'kelases'])
            ->where('id_hari', $hariId)
            ->whereIn('id_jam', $this->jams->pluck('id'));

        if ($kelasId !== 'all') {
            $query->whereHas('kelases', fn ($q) => $q->where('tbl_kelas.id', $kelasId));
        }

        $matrix = [];
        foreach ($query->orderBy('id')->get() as $jadwal) {
            foreach ($jadwal->kelases as $kelas) {
                $matrix[$kelas->id][$jadwal->id_jam][] = $jadwal;
            }
        }

        return $matrix;
    }

    // ------------------------------------------------------------------
    // Actions (modal) — dipanggil dari view: mountAction('nama', {argumen})
    // ------------------------------------------------------------------

    public function tambahSesiAction(): Action
    {
        return Action::make('tambahSesi')
            ->label('Tambah sesi')
            ->modalHeading('Tambah sesi mengajar')
            ->form(fn () => $this->sesiFormSchema())
            ->action(function (array $data, array $arguments, Action $action): void {
                $this->simpanSesi($data, (int) ($arguments['jam'] ?? 0), null, $action);
            });
    }

    public function ubahSesiAction(): Action
    {
        return Action::make('ubahSesi')
            ->label('Ubah')
            ->modalHeading('Ubah sesi mengajar')
            ->fillForm(function (array $arguments): array {
                $jadwal = $this->cariSesi((int) ($arguments['jadwal'] ?? 0));

                if (! $jadwal) {
                    return [];
                }

                $anchor = $this->selectedKelasId();

                return [
                    'id_mapel' => $jadwal->id_mapel,
                    'id_guru' => $jadwal->id_guru,
                    'nama_kelompok' => $jadwal->nama_kelompok,
                    'kelas_gabungan' => $jadwal->kelases
                        ->pluck('id')
                        ->reject(fn ($id) => (int) $id === $anchor)
                        ->values()
                        ->all(),
                ];
            })
            ->form(fn () => $this->sesiFormSchema())
            ->action(function (array $data, array $arguments, Action $action): void {
                $this->simpanSesi(
                    $data,
                    (int) ($arguments['jam'] ?? 0),
                    (int) ($arguments['jadwal'] ?? 0),
                    $action,
                );
            });
    }

    public function hapusSesiAction(): Action
    {
        return Action::make('hapusSesi')
            ->label('Hapus')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus sesi mengajar?')
            ->modalDescription(function (array $arguments): string {
                $jadwal = $this->cariSesi((int) ($arguments['jadwal'] ?? 0));

                if (! $jadwal) {
                    return 'Sesi tidak ditemukan.';
                }

                $kelas = $jadwal->kelases->map(fn ($k) => $k->kelas . ' ' . $k->nama_kelas)->join(', ');

                return "Sesi ini akan dihapus dari semua kelas yang mengikutinya: {$kelas}.";
            })
            ->action(function (array $arguments): void {
                $jadwal = $this->cariSesi((int) ($arguments['jadwal'] ?? 0));

                if (! $jadwal) {
                    Notification::make()
                        ->title('Sesi tidak ditemukan.')
                        ->body('Muat ulang halaman lalu coba lagi.')
                        ->warning()
                        ->send();

                    return;
                }

                $jadwal->delete(); // baris pivot ikut terhapus (ON DELETE CASCADE)

                Notification::make()->title('Sesi dihapus.')->success()->send();
            });
    }

    public function generateJadwalManualAction(): Action
    {
        return Action::make('generateJadwalManual')
            ->label('Generate Jadwal Manual')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Generate presensi guru minggu ini?')
            ->modalDescription('Menjalankan proses yang sama dengan cron job mingguan. Presensi yang sudah ada dilewati, jadi aman dijalankan berulang.')
            ->modalSubmitActionLabel('Ya, generate')
            ->action(function (): void {
                try {
                    $exitCode = Artisan::call('presensi:generate-guru-weekly');
                    $output = Artisan::output();
                } catch (\Throwable $e) {
                    Log::error('Generate jadwal manual gagal: ' . $e->getMessage());

                    Notification::make()
                        ->title('Generate gagal.')
                        ->body('Mohon cek log sistem.')
                        ->danger()
                        ->send();

                    return;
                }

                if ($exitCode !== 0) {
                    Log::error('Generate jadwal manual gagal', ['output' => $output]);

                    // Tampilkan baris error dari command, dipotong agar notifikasi tidak terlalu panjang
                    preg_match('/Terjadi error selama generasi:[^\n]*/', $output, $match);

                    Notification::make()
                        ->title('Generate gagal.')
                        ->body(str($match[0] ?? 'Mohon cek log sistem.')->limit(200)->toString())
                        ->danger()
                        ->send();

                    return;
                }

                // Ringkasan dibaca dari output command (mengandalkan teks pesan di command)
                $dibuat = substr_count($output, 'Berhasil membuat:');
                $sudahAda = substr_count($output, 'Melewatkan (sudah ada)');
                $tanpaKelas = substr_count($output, 'tidak punya kelas');

                $body = "{$dibuat} presensi baru dibuat, {$sudahAda} sudah ada sebelumnya.";
                if ($tanpaKelas > 0) {
                    $body .= " {$tanpaKelas} sesi dilewati karena tidak punya kelas.";
                }

                Notification::make()
                    ->title('Generate selesai.')
                    ->body($body)
                    ->success()
                    ->send();
            });
    }

    // ------------------------------------------------------------------
    // Form & aturan bisnis
    // ------------------------------------------------------------------

    protected function sesiFormSchema(): array
    {
        return [
            Forms\Components\Select::make('id_mapel')
                ->label('Mata Pelajaran')
                ->options(fn () => $this->mapels->pluck('mata_pelajaran', 'id'))
                ->searchable()
                ->required()
                ->live(),

            Forms\Components\Select::make('id_guru')
                ->label('Guru')
                ->options(fn () => $this->gurus->pluck('nama_guru', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('nama_kelompok')
                ->label('Nama kelompok')
                ->helperText('Pembeda sesi paralel di kelas yang sama, mis. "Islam" atau "Kristen".')
                ->maxLength(255)
                ->visible(fn (Get $get) => $this->mapelPunya($get('id_mapel'), 'boleh_paralel'))
                ->required(fn (Get $get) => $this->mapelPunya($get('id_mapel'), 'boleh_paralel')),

            Forms\Components\Select::make('kelas_gabungan')
                ->label('Gabung dengan kelas')
                ->multiple()
                ->options(fn () => $this->kelases
                    ->where('id', '!=', $this->selectedKelasId())
                    ->mapWithKeys(fn ($k) => [$k->id => $k->kelas . ' ' . $k->nama_kelas])
                    ->all())
                ->visible(fn (Get $get) => $this->mapelPunya($get('id_mapel'), 'boleh_gabung')),
        ];
    }

    protected function mapelPunya(mixed $mapelId, string $flag): bool
    {
        return filled($mapelId)
            && (bool) $this->mapels->firstWhere('id', (int) $mapelId)?->{$flag};
    }

    /** Kelas yang sedang dipilih di filter (null kalau "Semua Kelas"). */
    protected function selectedKelasId(): ?int
    {
        $id = $this->data['selectedKelasId'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    /** Ambil sesi milik hari + kelas terpilih. Argumen dari browser tidak dipercaya begitu saja. */
    protected function cariSesi(int $jadwalId): ?Jadwal
    {
        $anchor = $this->selectedKelasId();
        $hariId = (int) ($this->data['selectedHariId'] ?? 0);

        if (! $jadwalId || ! $anchor || ! $hariId) {
            return null;
        }

        return Jadwal::with(['mapel', 'kelases'])
            ->where('id', $jadwalId)
            ->where('id_hari', $hariId)
            ->whereHas('kelases', fn ($q) => $q->where('tbl_kelas.id', $anchor))
            ->first();
    }

    protected function tolak(Action $action, string $judul, ?string $isi = null): never
    {
        Notification::make()->title($judul)->body($isi)->danger()->send();

        $action->halt();
    }

    protected function simpanSesi(array $data, int $jamId, ?int $jadwalId, Action $action): void
    {
        $hariId = (int) ($this->data['selectedHariId'] ?? 0);
        $anchor = $this->selectedKelasId();

        if (! $hariId || ! $anchor) {
            $this->tolak($action, 'Pilih Hari dan kelas spesifik terlebih dahulu.');
        }

        if (! $this->jams->contains('id', $jamId)) {
            $this->tolak($action, 'Jam tidak valid untuk hari ini.', 'Muat ulang halaman lalu coba lagi.');
        }

        // Baca mapel langsung dari DB agar penanda boleh_gabung/boleh_paralel selalu terbaru
        $mapel = Mapel::find($data['id_mapel'] ?? null);
        $guruId = (int) ($data['id_guru'] ?? 0);

        if (! $mapel || ! Guru::whereKey($guruId)->exists()) {
            $this->tolak($action, 'Mata pelajaran atau guru tidak valid.');
        }

        $kelompok = null;
        if ($mapel->boleh_paralel) {
            $kelompok = trim((string) ($data['nama_kelompok'] ?? ''));

            if ($kelompok === '') {
                $this->tolak($action, 'Nama kelompok wajib diisi untuk mapel paralel.');
            }
        }

        $gabungan = $mapel->boleh_gabung
            ? array_map('intval', $data['kelas_gabungan'] ?? [])
            : [];

        $kelasIds = collect([$anchor, ...$gabungan])->unique()->values();

        if (Kelas::whereIn('id', $kelasIds)->count() !== $kelasIds->count()) {
            $this->tolak($action, 'Kelas tidak valid.');
        }

        $existing = null;
        if ($jadwalId) {
            $existing = $this->cariSesi($jadwalId);

            if (! $existing || (int) $existing->id_jam !== $jamId) {
                $this->tolak($action, 'Sesi tidak ditemukan.', 'Muat ulang halaman lalu coba lagi.');
            }
        }

        $error = null;

        try {
            DB::transaction(function () use ($hariId, $jamId, $mapel, $guruId, $kelompok, $kelasIds, $existing) {
                // Kunci baris kelas agar dua admin tidak bisa menyimpan bentrok bersamaan
                Kelas::whereIn('id', $kelasIds)->orderBy('id')->lockForUpdate()->get();

                $slot = fn () => Jadwal::query()
                    ->where('id_hari', $hariId)
                    ->where('id_jam', $jamId)
                    ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id));

                // Aturan 1: satu guru hanya satu sesi pada hari+jam yang sama
                if ($slot()->where('id_guru', $guruId)->exists()) {
                    throw new \DomainException('Guru ini sudah mengajar di sesi lain pada hari dan jam yang sama.');
                }

                // Aturan 2: kelas yang sudah terisi hanya boleh ditambah sesi bila
                // sesi baru DAN sesi yang sudah ada sama-sama mapel paralel
                $sesiLain = $slot()
                    ->with(['mapel', 'kelases'])
                    ->whereHas('kelases', fn ($q) => $q->whereIn('tbl_kelas.id', $kelasIds))
                    ->get();

                foreach ($sesiLain as $sesi) {
                    $kelasBentrok = $sesi->kelases
                        ->whereIn('id', $kelasIds)
                        ->map(fn ($k) => $k->kelas . ' ' . $k->nama_kelas)
                        ->join(', ');

                    if (! ($mapel->boleh_paralel && $sesi->mapel?->boleh_paralel)) {
                        throw new \DomainException(
                            "Kelas {$kelasBentrok} sudah memiliki pelajaran {$sesi->mapel?->mata_pelajaran} pada jam ini."
                        );
                    }

                    if (
                        (int) $sesi->id_mapel === (int) $mapel->id
                        && strcasecmp((string) $sesi->nama_kelompok, (string) $kelompok) === 0
                    ) {
                        throw new \DomainException(
                            "Kelas {$kelasBentrok} sudah memiliki kelompok \"{$kelompok}\" untuk mapel ini pada jam yang sama."
                        );
                    }
                }

                $atribut = [
                    'id_hari' => $hariId,
                    'id_jam' => $jamId,
                    'id_mapel' => $mapel->id,
                    'id_guru' => $guruId,
                    'nama_kelompok' => $kelompok,
                ];

                if ($existing) {
                    $existing->update($atribut);
                    $jadwal = $existing;
                } else {
                    $jadwal = Jadwal::create($atribut);
                }

                $jadwal->kelases()->sync($kelasIds->all());
            });
        } catch (\DomainException $e) {
            $error = $e->getMessage();
        } catch (QueryException $e) {
            Log::error('Jadwal QueryException', ['code' => $e->getCode(), 'message' => $e->getMessage()]);

            $error = str_contains($e->getMessage(), 'jadwal_unique_per_guru_per_jam')
                ? 'Guru ini sudah mengajar di sesi lain pada hari dan jam yang sama.'
                : 'Terjadi kesalahan database yang tidak terduga. Mohon cek log sistem.';
        } catch (\Throwable $e) {
            Log::error('Jadwal error: ' . $e->getMessage());

            $error = 'Terjadi kesalahan. Mohon cek log sistem.';
        }

        if ($error !== null) {
            $this->tolak($action, 'Gagal menyimpan jadwal.', $error);
        }

        Notification::make()
            ->title($existing ? 'Jadwal berhasil diperbarui.' : 'Jadwal berhasil ditambahkan.')
            ->success()
            ->send();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }
}