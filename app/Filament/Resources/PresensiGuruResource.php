<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PresensiGuruResource\Pages;
use App\Filament\Resources\PresensiGuruResource\RelationManagers;
use App\Models\PresensiGuru;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;

class PresensiGuruResource extends Resource
{
    protected static ?string $model = PresensiGuru::class; // Model yang digunakan

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list'; // Ikon laporan
    protected static ?string $navigationGroup = 'Laporan'; // Letakkan di grup Laporan
    protected static ?string $navigationLabel = 'Laporan Presensi Guru';
    protected static ?string $pluralLabel = 'Laporan Presensi Guru';
    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\LaporanPresensiGuru::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}
