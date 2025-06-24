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

class GuruResource extends Resource
{
    protected static ?string $model = Guru::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group'; // Ikon yang cocok
    protected static ?string $navigationGroup = 'Data Guru'; // Letakkan di group Manajemen Data
    protected static ?string $navigationLabel = 'Guru';
    protected static ?string $pluralLabel = 'Guru';
    protected static ?int $navigationSort = 5; // Urutkan setelah Siswa jika Siswa 20

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
                    ->required() // **Wajib diisi**
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    // Nonaktifkan field saat operasi "edit"
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    // Sembunyikan field saat operasi "edit" (opsional, tergantung preferensi UI)
                    // ->hidden(fn (string $operation): bool => $operation === 'edit')
                    ->helperText(new HtmlString('RFID **wajib diisi** saat membuat data baru. Tidak bisa diubah setelah dibuat. ' .
                                 'Jika perlu mengubah RFID, hapus data dan buat ulang, atau hubungi admin sistem.')),
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
                Tables\Columns\TextColumn::make('rfid')
                    ->searchable()
                    ->label('RFID')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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