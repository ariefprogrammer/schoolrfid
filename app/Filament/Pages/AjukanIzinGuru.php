<?php

namespace App\Filament\Pages;

use App\Models\Guru;
use App\Services\HonorGuruService;
use Filament\Pages\Page;
use Filament\Forms\Components;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class AjukanIzinGuru extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static string $view = 'filament.pages.ajukan-izin-guru';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Sembunyikan dari menu
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Components\Select::make('id_guru')
                    ->label('Pilih Guru')
                    ->options(Guru::all()->pluck('nama_guru', 'id'))
                    ->searchable()
                    ->required(),

                Components\DatePicker::make('tanggal')
                    ->label('Tanggal Izin')
                    ->default(now())
                    ->required(),

                Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                    ])
                    ->required(),

                Components\Radio::make('jenis_izin')
                    ->label('Jenis')
                    ->options([
                        'dengan_tugas' => 'Dengan tugas',
                        'tanpa_tugas' => 'Tanpa tugas',
                    ])
                    ->inline()
                    ->required()
                    ->helperText('Menentukan persentase honor yang tetap dibayarkan (diatur di Pengaturan Aplikasi).'),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();

        // Hanya jam yang belum presensi dan bukan hari libur yang diubah
        $jumlah = app(HonorGuruService::class)->catatIzin(
            (int) $data['id_guru'],
            $data['tanggal'],
            $data['status'],
            $data['jenis_izin'],
        );

        if ($jumlah === 0) {
            $pesan = 'Tidak ada jadwal yang bisa diberi izin pada tanggal tersebut (belum ada jadwal, sudah presensi, atau hari libur).';

            Notification::make()
                ->title('Gagal!')
                ->body($pesan)
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.tanggal' => [$pesan],
            ]);
        }

        Notification::make()
            ->title('Berhasil!')
            ->body($jumlah . ' jam pelajaran guru berhasil dicatat sebagai ' . $data['status'] . '.')
            ->success()
            ->send();

        return to_route('filament.administrator.resources.izin-gurus.index');
    }

    public static function getNavigationLabel(): string
    {
        return 'Ajukan Izin Guru';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Presensi';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }
}