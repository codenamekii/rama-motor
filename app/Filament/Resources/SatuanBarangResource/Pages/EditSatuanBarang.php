<?php

namespace App\Filament\Resources\SatuanBarangResource\Pages;

use App\Filament\Resources\SatuanBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSatuanBarang extends EditRecord
{
    protected static string $resource = SatuanBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->before(function (Actions\DeleteAction $action) {
                if ($this->record->barangs()->exists()) {
                    \Filament\Notifications\Notification::make()->danger()->title('Tidak dapat dihapus')->body('Satuan barang ini masih memiliki barang terkait.')->persistent()->send();

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
        return 'Satuan barang berhasil diperbarui';
    }
}
