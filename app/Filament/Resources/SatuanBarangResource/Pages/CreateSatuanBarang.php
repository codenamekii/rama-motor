<?php

namespace App\Filament\Resources\SatuanBarangResource\Pages;

use App\Filament\Resources\SatuanBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSatuanBarang extends CreateRecord
{
    protected static string $resource = SatuanBarangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Satuan barang berhasil ditambahkan';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['kode'] = \App\Models\SatuanBarang::generateKode();

        return $data;
    }
}
