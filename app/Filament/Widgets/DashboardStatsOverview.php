<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\TransaksiMasuk;
use App\Models\TransaksiKeluar;
use App\Models\Barang;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Penjualan hari ini
        $penjualanHariIni = TransaksiKeluar::whereDate('tanggal_transaksi', today())->sum('total_bayar');
        $transaksiHariIni = TransaksiKeluar::whereDate('tanggal_transaksi', today())->count();

        // Penjualan bulan ini
        $penjualanBulanIni = TransaksiKeluar::whereMonth('tanggal_transaksi', now()->month)->whereYear('tanggal_transaksi', now()->year)->sum('total_bayar');

        // Laba bulan ini (only for super_admin)
        $labaBulanIni = 0;
        if (Auth::check() && Auth::user()->role === 'super_admin') {
            $labaBulanIni = TransaksiKeluar::whereMonth('tanggal_transaksi', now()->month)->whereYear('tanggal_transaksi', now()->year)->get()->sum('total_laba');
        }

        // Total hutang
        $totalHutang = TransaksiMasuk::where('status_pembayaran', '!=', 'Lunas')->sum('sisa_hutang');

        // Total piutang
        $totalPiutang = TransaksiKeluar::where('status_pembayaran', '!=', 'Lunas')->sum('sisa_piutang');

        // Stok warning
        $stokMenipis = Barang::whereColumn('stok', '<=', 'stok_minimal')->where('stok', '>', 0)->where('is_active', true)->count();

        $stokHabis = Barang::where('stok', 0)->where('is_active', true)->count();

        // Total pelanggan aktif
        $totalPelanggan = Pelanggan::where('is_active', true)->count();

        $stats = [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($penjualanHariIni, 0, ',', '.'))
                ->description($transaksiHariIni . ' transaksi')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart([7, 3, 4, 5, 6, 3, 5])
                ->color('success'),

            Stat::make('Penjualan Bulan Ini', 'Rp ' . number_format($penjualanBulanIni, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->chart([3, 4, 4, 5, 6, 7, 8])
                ->color('primary'),
        ];

        // Add laba stats for super_admin
        if (Auth::check() && Auth::user()->role === 'super_admin') {
            $stats[] = Stat::make('Laba Bulan Ini', 'Rp ' . number_format($labaBulanIni, 0, ',', '.'))
                ->description('Keuntungan bersih')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([2, 3, 3, 5, 4, 6, 7])
                ->color('info');
        }

        $stats = array_merge($stats, [
            Stat::make('Stok Warning', $stokMenipis + $stokHabis . ' item')
                ->description($stokHabis . ' habis, ' . $stokMenipis . ' menipis')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stokHabis > 0 ? 'danger' : 'warning')
                ->extraAttributes([
                    'class' => $stokHabis > 0 ? 'ring-2 ring-red-500' : '',
                ]),

            Stat::make('Total Hutang', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description('Ke pemasok')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color($totalHutang > 0 ? 'danger' : 'gray'),

            Stat::make('Total Piutang', 'Rp ' . number_format($totalPiutang, 0, ',', '.'))
                ->description('Dari pelanggan')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color($totalPiutang > 0 ? 'warning' : 'gray'),
        ]);

        return $stats;
    }

    protected static ?string $pollingInterval = '30s';
}
