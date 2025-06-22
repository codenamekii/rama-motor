<?php
// File: app/Filament/Resources/TransaksiMasukResource/Pages/ListTransaksiMasuks.php
namespace App\Filament\Resources\TransaksiMasukResource\Pages;

use App\Filament\Resources\TransaksiMasukResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransaksiMasuks extends ListRecords
{
    protected static string $resource = TransaksiMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah Transaksi Masuk')->icon('heroicon-o-plus')];
    }

    protected function getHeaderWidgets(): array
    {
        return [TransaksiMasukResource\Widgets\TransaksiMasukStatsWidget::class];
    }
}
