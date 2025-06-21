<?php

namespace App\Filament\Resources\JenisBarangResource\Pages;

use App\Filament\Resources\JenisBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJenisBarang extends CreateRecord
{
    protected static string $resource = JenisBarangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jenis barang berhasil ditambahkan';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['kode'] = \App\Models\JenisBarang::generateKode();

        return $data;
    }
}
