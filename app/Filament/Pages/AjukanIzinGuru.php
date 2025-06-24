<?php

namespace App\Filament\Pages;

use App\Models\Guru;
use App\Models\PresensiGuru;
use Filament\Pages\Page;
use Filament\Forms\Components;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redirect;

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
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();

        $idGuru = $data['id_guru'];
        $tanggal = $data['tanggal'];
        $status = $data['status'];

        // Cek apakah guru punya jadwal di tanggal tersebut
        $exists = PresensiGuru::where('id_guru', $idGuru)
            ->whereDate('tanggal_jadwal', $tanggal)
            ->exists();

        if (!$exists) {
            Notification::make()
                ->title('Gagal!')
                ->body('Tidak ada jadwal guru ini pada tanggal tersebut.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'tanggal' => ['Tidak ada jadwal guru ini pada tanggal tersebut.'],
            ]);
        }

        // Update semua presensi guru di tanggal tersebut
        PresensiGuru::where('id_guru', $idGuru)
            ->whereDate('tanggal_jadwal', $tanggal)
            ->update([
                'status_in' => $status,
                'status_out' => $status,
            ]);

        Notification::make()
            ->title('Berhasil!')
            ->body('Status presensi guru berhasil diperbarui.')
            ->success()
            ->send();

        // $this->form->fill();
        // return redirect()->to(static::getUrl());
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