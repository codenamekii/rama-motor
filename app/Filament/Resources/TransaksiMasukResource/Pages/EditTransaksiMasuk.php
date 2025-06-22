<?php

namespace App\Filament\Resources\TransaksiMasukResource\Pages;

use App\Filament\Resources\TransaksiMasukResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaksiMasuk extends EditRecord
{
    protected static string $resource = TransaksiMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()->requiresConfirmation()->modalHeading('Hapus Transaksi')->modalDescription('Menghapus transaksi akan mengembalikan stok barang. Apakah Anda yakin?')->modalSubmitActionLabel('Ya, Hapus')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Transaksi masuk berhasil diperbarui';
    }

    protected function afterSave(): void
    {
        // Recalculate total
        $this->record->hitungTotal();
    }
}
