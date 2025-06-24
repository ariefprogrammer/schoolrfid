<?php

namespace App\Filament\Pages;

use App\Models\Pengaturan;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Models\User;

class AppSettings extends Page implements HasForms
{

    public static function canAccess(): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }
    
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';
    protected static ?string $title = 'Pengaturan Aplikasi';
    protected static string $view = 'filament.pages.pengaturan';
    protected static ?int $navigationSort = 14;

    public ?array $data = [];
    public ?Pengaturan $settings = null;

    public function mount(): void
    {
        $this->settings = Pengaturan::first();
        if (!$this->settings) {
            $this->settings = Pengaturan::create(); // Buat default jika belum ada
        }

        $this->form->fill($this->settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_aplikasi')
                    ->label('Nama Aplikasi')
                    ->maxLength(255)
                    ->placeholder('Contoh: Sistem Presensi RFID Sekolah'),

                TextInput::make('token_telegram')
                    ->label('Token Telegram Bot (Opsional)')
                    ->maxLength(255)
                    ->placeholder('Masukkan token bot Telegram Anda'),

                TextInput::make('telegram_kepsek')
                    ->label('Chat ID Telegram Kepala Sekolah (Opsional)')
                    ->maxLength(255)
                    ->placeholder('Contoh: 123456789'),

                TextInput::make('upah_perjam')
                    ->label('Upah Per Jam Guru (Rp)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required()
                    ->helperText('Digunakan untuk perhitungan honor guru'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $this->settings->update($data);

            Notification::make()
                ->title('Pengaturan berhasil disimpan!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan saat menyimpan pengaturan.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}