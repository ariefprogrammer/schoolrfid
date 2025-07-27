<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuruResource\Pages;
use App\Filament\Resources\GuruResource\RelationManagers;
use App\Models\Guru; // Pastikan model Guru diimport
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash; // Untuk hashing password
use Illuminate\Support\HtmlString; // Untuk helper text
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class GuruResource extends Resource
{
    protected static ?string $model = Guru::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group'; // Ikon yang cocok
    protected static ?string $navigationGroup = 'Data Guru'; // Letakkan di group Manajemen Data
    protected static ?string $navigationLabel = 'Guru';
    protected static ?string $pluralLabel = 'Guru';
    protected static ?int $navigationSort = 14; // Urutkan setelah Siswa jika Siswa 20

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->label('Kode Guru')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('nama_guru')
                    ->label('Nama Lengkap Guru')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)) // Hash password saat disimpan
                    ->dehydrated(fn (?string $state): bool => filled($state)) // Hanya proses jika diisi
                    ->required(fn (string $operation): bool => $operation === 'create') // Wajib diisi saat membuat, opsional saat mengedit
                    ->maxLength(255),
                Forms\Components\TextInput::make('rfid')
                    ->label('Kode RFID')
                    ->maxLength(255)
                    ->required(fn ($livewire) => $livewire instanceof \App\Filament\Resources\GuruResource\Pages\CreateGuru)
                    ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\GuruResource\Pages\CreateGuru)
                    ->unique(ignoreRecord: true)
                    ->helperText(new HtmlString('RFID <strong>wajib diisi</strong> saat membuat data baru.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->searchable()
                    ->sortable()
                    ->label('Kode'),
                Tables\Columns\TextColumn::make('rfid')
                    ->searchable()
                    ->label('RFID'),
                Tables\Columns\TextColumn::make('nip')
                    ->searchable()
                    ->sortable()
                    ->label('NIP'),
                Tables\Columns\TextColumn::make('nama_guru')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Guru'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label('Email'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat Pada'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Diperbarui Pada'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('gantiRfid')
                    ->label('')
                    ->icon('heroicon-o-key')
                    ->tooltip('Ganti RFID')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('rfid')
                            ->label('Kode RFID Baru')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, Guru $record) {
                        $record->rfid = $data['rfid'];
                        $record->save();

                        Notification::make()
                            ->title('RFID berhasil diperbarui')
                            ->success()
                            ->send();
                    }),


                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Edit'),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGurus::route('/'),
            'create' => Pages\CreateGuru::route('/create'),
            'edit' => Pages\EditGuru::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}