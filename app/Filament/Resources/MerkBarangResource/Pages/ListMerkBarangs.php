<?php

namespace App\Filament\Resources\MerkBarangResource\Pages;

use App\Filament\Resources\MerkBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMerkBarangs extends ListRecords
{
    protected static string $resource = MerkBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah Merk Barang')->icon('heroicon-o-plus')];
    }
}
