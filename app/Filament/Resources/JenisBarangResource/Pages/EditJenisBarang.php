<?php

namespace App\Filament\Resources\JenisBarangResource\Pages;

use App\Filament\Resources\JenisBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisBarang extends EditRecord
{
    protected static string $resource = JenisBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->before(function (Actions\DeleteAction $action) {
                if ($this->record->barangs()->exists()) {
                    \Filament\Notifications\Notification::make()->danger()->title('Tidak dapat dihapus')->body('Jenis barang ini masih memiliki barang terkait.')->persistent()->send();

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
        return 'Jenis barang berhasil diperbarui';
    }
}
