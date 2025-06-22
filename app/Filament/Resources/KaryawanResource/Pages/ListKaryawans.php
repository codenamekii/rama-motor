<?php

namespace App\Filament\Resources\KaryawanResource\Pages;

use App\Filament\Resources\KaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\KaryawanResource\Widgets\KaryawanStatsWidget;

class ListKaryawans extends ListRecords
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah Karyawan')->icon('heroicon-o-plus')];
    }

    protected function getHeaderWidgets(): array
    {
        return [KaryawanStatsWidget::class];
    }
}
