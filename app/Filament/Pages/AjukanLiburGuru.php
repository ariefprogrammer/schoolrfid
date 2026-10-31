<?php

namespace App\Filament\Pages;

use App\Models\PresensiGuru;
use Filament\Pages\Page;
use Filament\Forms\Components;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use App\Services\HonorGuruService;
use Illuminate\Validation\ValidationException;

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

        // Hanya jam yang belum presensi masuk yang ditandai libur dan dibayar 100%
        $jumlah = app(HonorGuruService::class)->catatLibur($tanggal);

        if ($jumlah === 0) {
            $pesan = 'Tidak ada jadwal guru yang bisa ditetapkan libur pada tanggal tersebut (belum ada jadwal, atau semua guru sudah presensi).';

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
            ->body("{$jumlah} jam pelajaran pada tanggal $tanggal ditetapkan libur dan dibayar 100%.")
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