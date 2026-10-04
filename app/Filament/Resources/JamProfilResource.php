<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JamProfilResource\Pages;
use App\Filament\Resources\JamProfilResource\RelationManagers;
use App\Models\JamProfil;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JamProfilResource extends Resource
{
    protected static ?string $model = JamProfil::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'Data Guru';
    protected static ?string $navigationLabel = 'Jam Pola';
    protected static ?string $pluralLabel = 'Jam Pola';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')->required()->maxLength(50),
            Forms\Components\Toggle::make('is_default')->label('Pola default'),
            Forms\Components\Repeater::make('jams')
                ->label('Jam pelajaran')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('ke')->numeric()->required(),
                    Forms\Components\TimePicker::make('jam_mulai')->seconds(false)->required(),
                    Forms\Components\TimePicker::make('jam_selesai')->seconds(false)->required()->after('jam_mulai'),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListJamProfils::route('/'),
            'create' => Pages\CreateJamProfil::route('/create'),
            'edit' => Pages\EditJamProfil::route('/{record}/edit'),
        ];
    }
}
