<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\TransaksiKeluar;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class LabaChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Laba Penjualan';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    public static function canView(): bool
    {
        $user = Auth::user();
        // Replace with your own role checking logic if not using a package
        return $user && $user->role === 'super_admin';
    }

    public function getHeading(): string|Htmlable|null
    {
        $totalLaba = $this->getMonthlyProfit();
        return 'Laba Bulan ' . now()->format('F Y') . ' - Rp ' . number_format($totalLaba, 0, ',', '.');
    }

    protected function getData(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $daysInMonth = now()->daysInMonth;

        $labels = [];
        $dataLaba = [];
        $dataOmzet = [];

        // Generate data for current month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($currentYear, $currentMonth, $day);
            $labels[] = $day;

            // Get transactions for this date
            $transaksi = TransaksiKeluar::whereDate('tanggal_transaksi', $date)->with('details')->get();

            // Calculate laba
            $labaHarian = $transaksi->sum('total_laba');
            $omzetHarian = $transaksi->sum('total_bayar');

            $dataLaba[] = $labaHarian / 1000000; // Convert to millions
            $dataOmzet[] = $omzetHarian / 1000000; // Convert to millions
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omzet (Juta)',
                    'data' => $dataOmzet,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                    'type' => 'bar',
                    'order' => 2,
                ],
                [
                    'label' => 'Laba (Juta)',
                    'data' => $dataLaba,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'type' => 'line',
                    'order' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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

    private function getMonthlyProfit(): float
    {
        return TransaksiKeluar::whereMonth('tanggal_transaksi', now()->month)->whereYear('tanggal_transaksi', now()->year)->get()->sum('total_laba');
    }
}
