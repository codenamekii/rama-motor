<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\TransaksiMasuk;
use App\Models\TransaksiKeluar;
use Illuminate\Support\Carbon;

class TransaksiChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Transaksi 30 Hari Terakhir';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = 30;
        $data = [];
        $labels = [];
        $dataTransaksiMasuk = [];
        $dataTransaksiKeluar = [];

        // Generate data for last 30 days
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');

            // Transaksi Masuk
            $totalMasuk = TransaksiMasuk::whereDate('tanggal_transaksi', $date)->sum('total_bayar');
            $dataTransaksiMasuk[] = $totalMasuk / 1000000; // Convert to millions

            // Transaksi Keluar
            $totalKeluar = TransaksiKeluar::whereDate('tanggal_transaksi', $date)->sum('total_bayar');
            $dataTransaksiKeluar[] = $totalKeluar / 1000000; // Convert to millions
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pembelian (Juta)',
                    'data' => $dataTransaksiMasuk,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Penjualan (Juta)',
                    'data' => $dataTransaksiKeluar,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => "
                            function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rp ' + context.parsed.y.toFixed(2) + ' Juta';
                                return label;
                            }
                        ",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "
                            function(value) {
                                return 'Rp ' + value + ' Jt';
                            }
                        ",
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
