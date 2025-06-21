<?php

namespace App\Filament\Resources\MerkBarangResource\Pages;

use App\Filament\Resources\MerkBarangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMerkBarang extends CreateRecord
{
    protected static string $resource = MerkBarangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Merk barang berhasil ditambahkan';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['kode'] = \App\Models\MerkBarang::generateKode();
        return $data;
    }
}
