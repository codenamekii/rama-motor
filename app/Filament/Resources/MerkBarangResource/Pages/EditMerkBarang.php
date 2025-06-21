<?php

namespace App\Filament\Resources\MerkBarangResource\Pages;

use App\Filament\Resources\MerkBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMerkBarang extends EditRecord
{
    protected static string $resource = MerkBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->before(function (Actions\DeleteAction $action) {
                if ($this->record->barangs()->exists()) {
                    \Filament\Notifications\Notification::make()->danger()->title('Tidak dapat dihapus')->body('Merk barang ini masih memiliki barang terkait.')->persistent()->send();

                    $action->cancel();
                }
            }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Merk barang berhasil diperbarui';
    }
}
