<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Produk Terlaris Bulan Ini';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Barang::query()
                    ->select('barangs.*', DB::raw('COALESCE(SUM(dtk.jumlah), 0) as total_terjual'))
                    ->leftJoin('detail_transaksi_keluars as dtk', 'barangs.id', '=', 'dtk.barang_id')
                    ->leftJoin('transaksi_keluars as tk', 'dtk.transaksi_keluar_id', '=', 'tk.id')
                    ->where(function ($query) {
                        $query->whereNull('tk.id')->orWhere(function ($q) {
                            $q->whereMonth('tk.tanggal_transaksi', now()->month)->whereYear('tk.tanggal_transaksi', now()->year);
                        });
                    })
                    ->where('barangs.is_active', true)
                    ->groupBy('barangs.id')
                    ->orderBy('total_terjual', 'desc')
                    ->limit(10),
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->size('sm'),

                Tables\Columns\TextColumn::make('nama')->label('Nama Barang')->searchable()->size('sm')->weight('medium')->description(fn($record) => $record->jenisBarang->nama . ' - ' . $record->merkBarang->nama),

                Tables\Columns\TextColumn::make('harga_jual')->label('Harga')->money('IDR')->size('sm'),

                Tables\Columns\TextColumn::make('total_terjual')->label('Terjual')->badge()->color('success')->formatStateUsing(fn($state, $record) => number_format($state) . ' ' . $record->satuanBarang->singkatan)->sortable(),

                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok Saat Ini')
                    ->badge()
                    ->color(
                        fn($state) => match (true) {
                            $state === 0 => 'danger',
                            $state <= 10 => 'warning',
                            default => 'gray',
                        },
                    )
                    ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->satuanBarang->singkatan),

                Tables\Columns\TextColumn::make('revenue')->label('Revenue')->getStateUsing(fn($record) => $record->total_terjual * $record->harga_jual)->money('IDR')->size('sm')->weight('medium')->color('primary'),
            ])
            ->paginated(false)
            ->poll('60s');
    }
}
