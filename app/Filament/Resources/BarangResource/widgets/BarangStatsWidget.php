<?php

namespace App\Filament\Resources\BarangResource\Widgets;

use App\Models\Barang;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BarangStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalBarang = Barang::count();
        $barangAktif = Barang::where('is_active', true)->count();
        $stokHabis = Barang::where('stok', 0)->count();
        $stokMenipis = Barang::whereColumn('stok', '<=', 'stok_minimal')->where('stok', '>', 0)->count();
        $nilaiInventori = Barang::where('is_active', true)->get()->sum('nilai_stok');

        return [
            Stat::make('Total Barang', number_format($totalBarang))
                ->description($barangAktif . ' barang aktif')
                ->icon('heroicon-o-archive-box')
                ->color('primary'),

            Stat::make('Stok Habis', $stokHabis)
                ->description('Perlu restock segera')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($stokHabis > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => $stokHabis > 0 ? 'ring-2 ring-red-500' : '',
                ]),

            Stat::make('Stok Menipis', $stokMenipis)
                ->description('Dibawah stok minimal')
                ->icon('heroicon-o-exclamation-circle')
                ->color($stokMenipis > 0 ? 'warning' : 'success'),

            Stat::make('Nilai Inventori', 'Rp ' . number_format($nilaiInventori, 0, ',', '.'))
                ->description('Total nilai stok')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }

    protected static ?string $pollingInterval = '30s';
}
