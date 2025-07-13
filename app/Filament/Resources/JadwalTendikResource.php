<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JadwalTendikResource\Pages;
use App\Models\JadwalTendik;
use App\Models\Tendik;
use App\Models\Hari;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class JadwalTendikResource extends Resource
{
    protected static ?string $model = JadwalTendik::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Data Tendik';
    protected static ?string $navigationLabel = 'Jadwal Tendik';
    protected static ?int $navigationSort = 21;

    public static function getEloquentQuery(): Builder
    {
        return JadwalTendik::query()
            ->selectRaw('MIN(id) as id, tendik_id, GROUP_CONCAT(hari_id) as hari_ids')
            ->groupBy('tendik_id')
            ->with('tendik');

    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('tendik_id')
                            ->label('Tendik')
                            ->options(fn () => Tendik::pluck('nama', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Repeater::make('jadwal')
                            ->label('Jadwal')
                            ->createItemButtonLabel('Tambah Hari')
                            ->default(function (?JadwalTendik $record) {
                                if (!$record) {
                                    return [];
                                }

                                return JadwalTendik::where('tendik_id', $record->tendik_id)
                                    ->get()
                                    ->map(fn ($jadwal) => [
                                        'hari_id' => $jadwal->hari_id,
                                        'jadwal_masuk' => $jadwal->jadwal_masuk,
                                        'jadwal_keluar' => $jadwal->jadwal_keluar,
                                    ])
                                    ->toArray();
                            })
                            ->schema([
                                Forms\Components\Select::make('hari_id')
                                    ->label('Hari')
                                    ->options(\App\Models\Hari::orderBy('order')->pluck('hari', 'id'))
                                    ->required(),
                                Forms\Components\TimePicker::make('jadwal_masuk')
                                    ->label('Jam Masuk')
                                    ->required(),
                                Forms\Components\TimePicker::make('jadwal_keluar')
                                    ->label('Jam Keluar')
                                    ->required(),
                            ])
                            ->minItems(1)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tendik.nama')
                    ->label('Tendik'),

                Tables\Columns\TextColumn::make('jadwal_summary')
                    ->label('Jadwal')
                    ->getStateUsing(function ($record) {
                        $jadwals = JadwalTendik::where('tendik_id', $record->tendik_id)
                            ->with('hari')
                            ->get();

                        return $jadwals->map(function ($item) {
                            return "{$item->hari->hari} ({$item->jadwal_masuk} - {$item->jadwal_keluar})";
                        })->implode(', ');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJadwalTendiks::route('/'),
            'create' => Pages\CreateJadwalTendik::route('/create'),
            'edit' => Pages\EditJadwalTendik::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}
