<?php

namespace App\Filament\Resources\JenisBarangResource\Widgets;

use App\Models\JenisBarang;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JenisBarangStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalJenis = JenisBarang::count();
        $jenisAktif = JenisBarang::where('is_active', true)->count();
        $totalBarang = JenisBarang::withCount('barangs')->get()->sum('barangs_count');

        return [
            Stat::make('Total Jenis Barang', $totalJenis)->description('Semua jenis barang')->icon('heroicon-o-tag')->color('primary'),

            Stat::make('Jenis Aktif', $jenisAktif)
                ->description($totalJenis - $jenisAktif . ' nonaktif')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Barang', number_format($totalBarang))->description('Dari semua jenis')->icon('heroicon-o-cube')->color('info'),
        ];
    }
}
