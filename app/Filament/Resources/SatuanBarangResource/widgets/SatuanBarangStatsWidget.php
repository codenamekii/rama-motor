<?php

namespace App\Filament\Resources\SatuanBarangResource\Widgets;

use App\Models\SatuanBarang;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SatuanBarangStatsWidget extends BaseWidget
{
  protected function getStats(): array
  {
    $totalSatuan = SatuanBarang::count();
    $satuanAktif = SatuanBarang::where('is_active', true)->count();
    $totalBarang = SatuanBarang::withCount('barangs')->get()->sum('barangs_count');

    return [
      Stat::make('Total Satuan Barang', $totalSatuan)->description('Semua satuan barang')->icon('heroicon-o-tag')->color('primary'),

      Stat::make('Satuan Aktif', $satuanAktif)
        ->description($totalSatuan - $satuanAktif . ' nonaktif')
        ->icon('heroicon-o-check-circle')
        ->color('success'),

      Stat::make('Total Barang', number_format($totalBarang))->description('Dari semua satuan')->icon('heroicon-o-cube')->color('info'),
    ];
  }
}
