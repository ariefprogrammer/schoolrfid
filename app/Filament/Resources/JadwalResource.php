<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JadwalResource\Pages;
use App\Filament\Resources\JadwalResource\RelationManagers;
use App\Models\Jadwal; // Import model Jadwal
use App\Models\Hari;   // Import model Hari
use App\Models\Jam;    // Import model Jam
use App\Models\Guru;   // Import model Guru
use App\Models\Mapel;  // Import model Mapel
use App\Models\Kelas;  // Import model Kelas
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;

class JadwalResource extends Resource
{
    protected static ?string $model = Jadwal::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar'; // Ikon kalender
    protected static ?int $navigationSort = 50; // Urutan pertama di group Jadwal
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_hari')
                    ->label('Hari')
                    ->required()
                    ->options(Hari::all()->pluck('hari', 'id')) // Ambil nama hari dari model Hari
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('id_jam')
                    ->label('Jam Pelajaran Ke-')
                    ->required()
                    ->options(Jam::all()->mapWithKeys(function ($jam) {
                        return [$jam->id => 'Jam Ke-' . $jam->ke . ' (' . \Carbon\Carbon::parse($jam->jam_mulai)->format('H:i') . ' - ' . \Carbon\Carbon::parse($jam->jam_selesai)->format('H:i') . ')'];
                    }))
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('id_kelas')
                    ->label('Kelas')
                    ->required()
                    ->options(Kelas::all()->mapWithKeys(function ($kelas) {
                        return [$kelas->id => $kelas->kelas . ' ' . $kelas->nama_kelas];
                    }))
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('id_mapel')
                    ->label('Mata Pelajaran')
                    ->required()
                    ->options(Mapel::all()->pluck('mata_pelajaran', 'id')) // Ambil nama mata pelajaran
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('id_guru')
                    ->label('Guru Pengajar')
                    ->required()
                    ->options(Guru::all()->pluck('nama_guru', 'id')) // Ambil nama guru
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hari.hari')
                    ->searchable()
                    ->sortable()
                    ->label('Hari'),
                Tables\Columns\TextColumn::make('jam.ke')
                    ->searchable()
                    ->sortable()
                    ->label('Jam Ke-')
                    ->description(fn (Jadwal $record): string => \Carbon\Carbon::parse($record->jam->jam_mulai)->format('H:i') . ' - ' . \Carbon\Carbon::parse($record->jam->jam_selesai)->format('H:i')),
                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->searchable()
                    ->sortable()
                    ->label('Kelas')
                    ->formatStateUsing(fn ($record) => $record->kelas->kelas . ' ' . $record->kelas->nama_kelas),
                Tables\Columns\TextColumn::make('mapel.mata_pelajaran')
                    ->searchable()
                    ->sortable()
                    ->label('Mata Pelajaran'),
                Tables\Columns\TextColumn::make('guru.nama_guru')
                    ->searchable()
                    ->sortable()
                    ->label('Guru Pengajar'),
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
                // Filter berdasarkan hari, kelas, guru, mapel
                Tables\Filters\SelectFilter::make('id_hari')
                    ->label('Hari')
                    ->options(Hari::all()->pluck('hari', 'id')),
                Tables\Filters\SelectFilter::make('id_kelas')
                    ->label('Kelas')
                    ->options(Kelas::all()->mapWithKeys(fn ($kelas) => [$kelas->id => $kelas->kelas . ' ' . $kelas->nama_kelas])),
                Tables\Filters\SelectFilter::make('id_guru')
                    ->label('Guru')
                    ->options(Guru::all()->pluck('nama_guru', 'id')),
                Tables\Filters\SelectFilter::make('id_mapel')
                    ->label('Mata Pelajaran')
                    ->options(Mapel::all()->pluck('mata_pelajaran', 'id')),
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
            'index' => Pages\ListJadwals::route('/'),
            'create' => Pages\CreateJadwal::route('/create'),
            'edit' => Pages\EditJadwal::route('/{record}/edit'),
        ];
    }
}