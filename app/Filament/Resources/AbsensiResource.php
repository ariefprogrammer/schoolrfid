<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Filament\Resources\AbsensiResource\RelationManagers;
use App\Models\Absensi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text'; // Ikon untuk laporan
    protected static ?string $navigationGroup = 'Laporan'; // Grup navigasi
    protected static ?string $navigationLabel = 'Laporan Presensi Siswa'; // Label menu di sidebar
    protected static ?int $navigationSort = 6; // Untuk mengatur urutan menu

    // Hapus atau kosongkan metode form dan table
    // Kita tidak akan menggunakan CRUD standar Filament di sini
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([])->actions([])->bulkActions([]);
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
            // Nonaktifkan halaman CRUD bawaan Filament, kita akan membuat halaman kustom
            // 'index' => Pages\ListAbsensis::route('/'),
            // 'create' => Pages\CreateAbsensi::route('/create'),
            // 'edit' => Pages\EditAbsensi::route('/{record}/edit'),
            'index' => Pages\LaporanPresensiSiswa::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}