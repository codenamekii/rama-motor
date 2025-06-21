<?php

namespace App\Filament\Resources\BarangResource\Pages;

use App\Filament\Resources\BarangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\BarangResource\Widgets\BarangStatsWidget;

class ListBarangs extends ListRecords
{
    protected static string $resource = BarangResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah Barang')->icon('heroicon-o-plus')];
    }

    protected function getHeaderWidgets(): array
    {
        return [BarangStatsWidget::class];
    }
}
