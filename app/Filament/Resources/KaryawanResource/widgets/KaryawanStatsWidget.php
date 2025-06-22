<?php

namespace App\Filament\Resources\KaryawanResource\Widgets;

use App\Models\Karyawan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KaryawanStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalKaryawan = Karyawan::count();
        $karyawanAktif = Karyawan::where('status_karyawan', 'Aktif')->count();
        $karyawanNonAktif = Karyawan::where('status_karyawan', 'Non-Aktif')->count();
        $karyawanResign = Karyawan::where('status_karyawan', 'Resign')->count();
        $totalGaji = Karyawan::where('status_karyawan', 'Aktif')->sum('gaji_pokok');

        return [
            Stat::make('Total Karyawan', $totalKaryawan)->description('Semua karyawan')->icon('heroicon-o-users')->color('primary'),

            Stat::make('Karyawan Aktif', $karyawanAktif)
                ->description($karyawanNonAktif . ' non-aktif, ' . $karyawanResign . ' resign')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Gaji/Bulan', 'Rp ' . number_format($totalGaji, 0, ',', '.'))
                ->description('Karyawan aktif')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
