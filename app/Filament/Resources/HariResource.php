<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HariResource\Pages;
use App\Filament\Resources\HariResource\RelationManagers;
use App\Models\Hari; // Pastikan model Hari diimport
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;

class HariResource extends Resource
{
    protected static ?string $model = Hari::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet'; // Ikon daftar/list
    protected static ?string $navigationGroup = 'Data Guru'; // Bisa dimasukkan ke grup Pengaturan
    protected static ?string $navigationLabel = 'Hari';
    protected static ?string $pluralLabel = 'Hari';
    protected static ?int $navigationSort = 6; // Urutkan setelah Pengaturan Aplikasi (jika Pengaturan Aplikasi 10)

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('hari')
                    ->label('Nama Hari')
                    ->required()
                    ->unique(ignoreRecord: true) // Tetap pastikan nama hari unik
                    ->options([ // Opsi dropdown
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                        'Minggu' => 'Minggu',
                    ])
                    ->placeholder('Pilih Hari'),
                Forms\Components\TextInput::make('order')
                    ->label('Urutan')
                    ->numeric() // Pastikan inputnya angka
                    ->required()
                    ->unique(ignoreRecord: true) // Pastikan urutan unik
                    ->minValue(1) // Urutan minimal 1
                    ->helperText('Contoh: 1 untuk Senin, 2 untuk Selasa, dst.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hari')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Hari'),
                Tables\Columns\TextColumn::make('order')
                    ->searchable()
                    ->sortable()
                    ->label('Urutan'),
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
            ])
            // Urutkan tabel berdasarkan kolom 'order' secara default
            ->defaultSort('order', 'asc');
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
            'index' => Pages\ListHaris::route('/'),
            'create' => Pages\CreateHari::route('/create'),
            'edit' => Pages\EditHari::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';

    }
}