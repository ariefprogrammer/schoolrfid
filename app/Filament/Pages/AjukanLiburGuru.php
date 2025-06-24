<?php

namespace App\Filament\Pages;

use App\Models\PresensiGuru;
use Filament\Pages\Page;
use Filament\Forms\Components;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class AjukanLiburGuru extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static string $view = 'filament.pages.ajukan-libur-guru';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Components\DatePicker::make('tanggal')
                    ->label('Tanggal Libur')
                    ->default(now())
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();
        $tanggal = $data['tanggal'];

        // Cek apakah ada jadwal pada tanggal ini
        $exists = PresensiGuru::whereDate('tanggal_jadwal', $tanggal)->exists();

        if (!$exists) {
            Notification::make()
                ->title('Gagal!')
                ->body('Tidak ada jadwal guru pada tanggal tersebut.')
                ->danger()
                ->send();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'tanggal' => ['Tidak ada jadwal guru pada tanggal tersebut.'],
            ]);
        }

        // Update semua presensi guru di tanggal ini menjadi 'libur'
        PresensiGuru::whereDate('tanggal_jadwal', $tanggal)
            ->update([
                'status_in' => 'libur',
                'status_out' => 'libur',
            ]);

        Notification::make()
            ->title('Berhasil!')
            ->body("Status semua guru pada tanggal $tanggal diubah menjadi libur.")
            ->success()
            ->send();

        // Redirect ke halaman index IzinGuruResource
        return redirect()->to(\App\Filament\Resources\IzinGuruResource::getUrl());
    }

    public static function getNavigationLabel(): string
    {
        return 'Ajukan Libur Guru';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-calendar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Presensi';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Sembunyikan dari menu
    }
}