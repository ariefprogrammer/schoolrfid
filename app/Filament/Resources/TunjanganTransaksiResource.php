<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TunjanganTransaksiResource\Pages;
use App\Models\TunjanganTransaksi;
use App\Services\TunjanganGuruService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TunjanganTransaksiResource extends Resource
{
    protected static ?string $model = TunjanganTransaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Rincian Tunjangan Guru';
    protected static ?string $modelLabel = 'Tunjangan Guru';
    protected static ?string $pluralModelLabel = 'Rincian Tunjangan Guru';
    protected static ?int $navigationSort = 26;

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    // Baris dibuat otomatis oleh sistem
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['guru', 'jenis']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guru.nama_guru')->label('Nama Guru')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('jenis.nama')->label('Tunjangan')->sortable(),
                Tables\Columns\TextColumn::make('jenis.tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'harian' ? 'Harian' : 'Bulanan')
                    ->color(fn ($state) => $state === 'harian' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('tanggal')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('paid')->label('Dibayar')->boolean(),
                Tables\Columns\IconColumn::make('disesuaikan')->label('Disesuaikan')->boolean(),
                Tables\Columns\TextColumn::make('keterangan')->label('Keterangan')->limit(40)->placeholder('-'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('id_guru')
                    ->label('Guru')
                    ->relationship('guru', 'nama_guru')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('id_jenis')
                    ->label('Tunjangan')
                    ->relationship('jenis', 'nama'),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari')->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai')->default(now()->endOfMonth()),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['dari'], fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
                        ->when($data['sampai'], fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))),
                Tables\Filters\TernaryFilter::make('paid')
                    ->label('Pembayaran')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah dibayar')
                    ->falseLabel('Belum dibayar'),
            ])
            ->actions([
                Tables\Actions\Action::make('sesuaikan')
                    ->label('Sesuaikan Nominal')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn ($record) => ! $record->paid)
                    ->fillForm(fn ($record) => [
                        'nominal' => $record->nominal,
                        'keterangan' => $record->keterangan,
                    ])
                    ->form([
                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal')
                            ->numeric()->minValue(0)->required()->prefix('Rp'),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Alasan penyesuaian')
                            ->required()->rows(3),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'nominal' => (int) $data['nominal'],
                            'keterangan' => $data['keterangan'],
                            'disesuaikan' => true,
                        ]);

                        Notification::make()->title('Nominal disesuaikan')->success()->send();
                    }),
                Tables\Actions\Action::make('kembalikan')
                    ->label('Kembalikan Otomatis')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn ($record) => ! $record->paid && $record->disesuaikan)
                    ->requiresConfirmation()
                    ->modalDescription('Nominal dikembalikan mengikuti penugasan dan pengaturan saat ini.')
                    ->action(function ($record) {
                        $record->update(['disesuaikan' => false, 'keterangan' => null]);

                        $service = app(TunjanganGuruService::class);

                        if ($record->jenis?->tipe === 'harian') {
                            $service->sinkronHarian((int) $record->id_guru, $record->tanggal->toDateString(), true);
                        } else {
                            $service->sinkronBulanan($record->periode, (int) $record->id_guru);
                        }

                        Notification::make()->title('Dikembalikan ke otomatis')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTunjanganTransaksis::route('/'),
        ];
    }
}