<?php

namespace App\Filament\Resources\TransaksiKeluarResource\Widgets;

use App\Models\TransaksiKeluar;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TransaksiKeluarStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = TransaksiKeluar::whereDate('tanggal_transaksi', today())->sum('total_bayar');
        $todayCount = TransaksiKeluar::whereDate('tanggal_transaksi', today())->count();

        $thisMonth = TransaksiKeluar::whereMonth('tanggal_transaksi', now()->month)->whereYear('tanggal_transaksi', now()->year)->sum('total_bayar');

        $todayLaba = TransaksiKeluar::whereDate('tanggal_transaksi', today())->get()->sum('total_laba');

        $monthLaba = TransaksiKeluar::whereMonth('tanggal_transaksi', now()->month)->whereYear('tanggal_transaksi', now()->year)->get()->sum('total_laba');

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($today, 0, ',', '.'))
                ->description($todayCount . ' transaksi')
                ->icon('heroicon-o-shopping-cart')
                ->color('success'),

            Stat::make('Penjualan Bulan Ini', 'Rp ' . number_format($thisMonth, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),

            Stat::make('Laba Hari Ini', 'Rp ' . number_format($todayLaba, 0, ',', '.'))
                ->description('Keuntungan bersih')
                ->icon('heroicon-o-currency-dollar')
                ->color('info'),
                // ->visible(fn() => Auth::user() && Auth::user()->role === 'super_admin'),

            Stat::make('Laba Bulan Ini', 'Rp ' . number_format($monthLaba, 0, ',', '.'))
                ->description('Total keuntungan ' . now()->format('F'))
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                // ->visible(fn() => Auth::user() && Auth::user()->role === 'super_admin'),
        ];
    }

    protected static ?string $pollingInterval = '30s';
}
