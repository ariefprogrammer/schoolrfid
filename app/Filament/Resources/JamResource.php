<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JamResource\Pages;
use App\Models\Jam;
use App\Models\JamProfil;
use Filament\Forms;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class JamResource extends Resource
{
    protected static ?string $model = Jam::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Data Guru';
    protected static ?string $navigationLabel = 'Jam Pelajaran';
    protected static ?string $pluralLabel = 'Jam Pelajaran';
    protected static ?int $navigationSort = 12;

    /**
     * ID pola jam default (Reguler).
     */
    protected static function defaultProfilId(): ?int
    {
        return JamProfil::where('is_default', true)->value('id');
    }

    /**
     * Resource ini hanya menampilkan jam milik pola default.
     * Jam pola lain (mis. Jumat) dikelola lewat resource Pola Jam.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('id_jam_profil', static::defaultProfilId());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Selalu dipaksa ke pola default saat disimpan,
                // sehingga tidak bisa diubah lewat manipulasi request.
                Forms\Components\Hidden::make('id_jam_profil')
                    ->default(fn () => static::defaultProfilId())
                    ->dehydrateStateUsing(fn () => static::defaultProfilId()),

                Forms\Components\TextInput::make('ke')
                    ->label('Jam Pelajaran Ke-')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    // Unik hanya di dalam pola default
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule
                            ->where('id_jam_profil', static::defaultProfilId())
                    )
                    ->helperText('Contoh: 1, 2, 3, dst.'),

                TimePicker::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->required()
                    ->seconds(false),

                TimePicker::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->required()
                    ->seconds(false)
                    ->after('jam_mulai'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ke')
                    ->searchable()
                    ->sortable()
                    ->label('Jam Ke-'),
                Tables\Columns\TextColumn::make('jam_mulai')
                    ->label('Jam Mulai'),
                Tables\Columns\TextColumn::make('jam_selesai')
                    ->label('Jam Selesai'),
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
            ->defaultSort('ke', 'asc');
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
            'index' => Pages\ListJams::route('/'),
            'create' => Pages\CreateJam::route('/create'),
            'edit' => Pages\EditJam::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }
}