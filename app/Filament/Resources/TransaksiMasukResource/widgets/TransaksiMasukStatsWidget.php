<?php

namespace App\Filament\Resources\TransaksiMasukResource\Widgets;

use App\Models\TransaksiMasuk;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransaksiMasukStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = TransaksiMasuk::whereDate('tanggal_transaksi', today())->sum('total_bayar');
        $thisMonth = TransaksiMasuk::whereMonth('tanggal_transaksi', now()->month)->whereYear('tanggal_transaksi', now()->year)->sum('total_bayar');
        $totalHutang = TransaksiMasuk::where('status_pembayaran', '!=', 'Lunas')->sum('sisa_hutang');
        $jatuhTempo = TransaksiMasuk::jatuhTempo()->count();

        return [
            Stat::make('Pembelian Hari Ini', 'Rp ' . number_format($today, 0, ',', '.'))
                ->description('Total transaksi hari ini')
                ->icon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Pembelian Bulan Ini', 'Rp ' . number_format($thisMonth, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->icon('heroicon-o-chart-bar')
                ->color('success'),

            Stat::make('Total Hutang', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description($jatuhTempo . ' transaksi jatuh tempo')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($totalHutang > 0 ? 'danger' : 'gray')
                ->extraAttributes([
                    'class' => $jatuhTempo > 0 ? 'ring-2 ring-red-500' : '',
                ]),
        ];
    }

    protected static ?string $pollingInterval = '30s';
}
