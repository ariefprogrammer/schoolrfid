<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KelasResource\Pages;
use App\Filament\Resources\KelasResource\RelationManagers;
use App\Models\Kelas; // Pastikan model Kelas diimport
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;

class KelasResource extends Resource
{
    protected static ?string $model = Kelas::class; // Tentukan model yang digunakan

    protected static ?string $navigationIcon = 'heroicon-o-bookmark'; // Anda bisa mengubah icon ini

    // Tambahkan label navigasi jika perlu
    protected static ?string $navigationLabel = 'Kelas';
    protected static ?string $pluralLabel = 'Kelas'; 
    protected static ?int $navigationSort = 16;
    protected static ?string $navigationGroup = 'Siswa';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kelas')
                    ->required()
                    ->maxLength(255)
                    ->label('Tingkatan Kelas'), // Label di form
                Forms\Components\TextInput::make('nama_kelas')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Kelas Lengkap'), // Label di form
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kelas')
                    ->searchable() // Tambahkan fitur pencarian
                    ->sortable() // Tambahkan fitur sorting
                    ->label('Tingkatan Kelas'),
                Tables\Columns\TextColumn::make('nama_kelas')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Kelas Lengkap'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true) // Sembunyikan secara default
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
                Tables\Actions\EditAction::make(), // Tombol edit
                Tables\Actions\DeleteAction::make(), // Tombol hapus
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(), // Aksi hapus massal
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
            'index' => Pages\ListKelas::route('/'),
            'create' => Pages\CreateKelas::route('/create'),
            'edit' => Pages\EditKelas::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}