<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\TransaksiKeluar;
use Illuminate\Support\Carbon;

class PaymentMethodChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Metode Pembayaran Bulan Ini';

    protected static ?int $sort = 7;

    protected static ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get payment method distribution
        $paymentMethods = TransaksiKeluar::whereMonth('tanggal_transaksi', $currentMonth)->whereYear('tanggal_transaksi', $currentYear)->selectRaw('jenis_pembayaran, COUNT(*) as total, SUM(total_bayar) as total_amount')->groupBy('jenis_pembayaran')->get();

        $labels = [];
        $data = [];
        $backgroundColors = [];
        $total = $paymentMethods->sum('total');

        foreach ($paymentMethods as $method) {
            $percentage = $total > 0 ? round(($method->total / $total) * 100, 1) : 0;
            $labels[] = $method->jenis_pembayaran . ' (' . $percentage . '%)';
            $data[] = $method->total;

            // Set colors based on payment method
            $backgroundColors[] = match ($method->jenis_pembayaran) {
                'Cash' => 'rgb(34, 197, 94)',
                'Transfer' => 'rgb(59, 130, 246)',
                'Kredit' => 'rgb(245, 158, 11)',
                'Debit' => 'rgb(168, 85, 247)',
                default => 'rgb(107, 114, 128)',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 15,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "
                            function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value + ' transaksi';
                            }
                        ",
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '60%',
        ];
    }
}
