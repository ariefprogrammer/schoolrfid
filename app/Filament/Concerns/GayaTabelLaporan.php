<?php

namespace App\Filament\Concerns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Gaya kolom yang dipakai bersama oleh halaman laporan (rekap presensi & gaji guru)
 * agar tampilan tabelnya konsisten.
 */
trait GayaTabelLaporan
{
    protected static function kolomNama(): TextColumn
    {
        return TextColumn::make('nama_guru')
            ->label('Nama Guru')
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('tbl_guru.nama_guru', 'like', "%{$search}%"))
            ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('nama_guru', $direction));
    }

    /** Kolom jumlah (jam/hari), tampil sebagai badge; abu-abu bila nol. */
    protected static function kolomAngka(string $nama, string $label, string $warna = 'gray'): TextColumn
    {
        return TextColumn::make($nama)
            ->label($label)
            ->alignCenter()
            ->sortable()
            ->badge()
            ->color(fn ($state) => (int) $state > 0 ? $warna : 'gray');
    }

    /** Kolom rupiah, rata kanan; abu-abu bila nol. */
    protected static function kolomRupiah(string $nama, string $label, string $warna = 'gray'): TextColumn
    {
        return TextColumn::make($nama)
            ->label($label)
            ->alignEnd()
            ->sortable()
            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
            ->color(fn ($state) => (int) $state > 0 ? $warna : 'gray');
    }

    protected static function kolomNomor(): TextColumn
    {
        return TextColumn::make('index')
            ->label('No')
            ->rowIndex();
    }
}