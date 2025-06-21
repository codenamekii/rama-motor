<?php

namespace App\Filament\Resources\BarangResource\Pages;

use App\Filament\Resources\BarangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBarang extends EditRecord
{
    protected static string $resource = BarangResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->requiresConfirmation()->modalHeading('Hapus Barang')->modalDescription('Apakah Anda yakin ingin menghapus barang ini? Tindakan ini tidak dapat dibatalkan.')->modalSubmitActionLabel('Ya, Hapus')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Barang berhasil diperbarui';
    }
}
