<?php
// File: app/Filament/Resources/SatuanBarangResource/Pages/ListSatuanBarangs.php
namespace App\Filament\Resources\SatuanBarangResource\Pages;

use App\Filament\Resources\SatuanBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSatuanBarangs extends ListRecords
{
  protected static string $resource = SatuanBarangResource::class;

  protected function getHeaderActions(): array
  {
    return [Actions\CreateAction::make()->label('Tambah Satuan Barang')->icon('heroicon-o-plus')];
  }

  protected function getHeaderWidgets(): array
  {
    return [ 
      SatuanBarangResource\Widgets\SatuanBarangStatsWidget::class,
    ];
  }
}
