<?php
// File: app/Filament/Resources/TransaksiKeluarResource/Pages/ListTransaksiKeluars.php
namespace App\Filament\Resources\TransaksiKeluarResource\Pages;

use App\Filament\Resources\TransaksiKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiKeluars extends ListRecords
{
    protected static string $resource = TransaksiKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah Transaksi Keluar')->icon('heroicon-o-plus')];
    }

    protected function getHeaderWidgets(): array
    {
        return [TransaksiKeluarResource\Widgets\TransaksiKeluarStatsWidget::class];
    }
}
