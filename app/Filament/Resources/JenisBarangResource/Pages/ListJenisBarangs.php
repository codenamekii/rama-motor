<?php
// File: app/Filament/Resources/JenisBarangResource/Pages/ListJenisBarangs.php
namespace App\Filament\Resources\JenisBarangResource\Pages;

use App\Filament\Resources\JenisBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisBarangs extends ListRecords
{
    protected static string $resource = JenisBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah Jenis Barang')->icon('heroicon-o-plus')];
    }

    protected function getHeaderWidgets(): array
    {
        return [JenisBarangResource\Widgets\JenisBarangStatsWidget::class];
    }
}
