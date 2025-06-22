<?php
// File: app/Filament/Pages/Dashboard.php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getColumns(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [\App\Filament\Widgets\DashboardStatsOverview::class, \App\Filament\Widgets\TransaksiChartWidget::class, \App\Filament\Widgets\LabaChartWidget::class, \App\Filament\Widgets\PaymentMethodChartWidget::class, \App\Filament\Widgets\StokKritisWidget::class, \App\Filament\Widgets\RecentTransactionsWidget::class, \App\Filament\Widgets\TopProductsWidget::class];
    }
}
