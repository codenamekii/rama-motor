<?php

namespace App\Filament\Resources\KaryawanResource\Pages;

use App\Filament\Resources\KaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKaryawan extends EditRecord
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->before(function () {
                    if ($this->record->status_karyawan === 'Aktif') {
                        \Filament\Notifications\Notification::make()->warning()->title('Perhatian')->body('Sebaiknya ubah status karyawan menjadi Resign/Non-Aktif terlebih dahulu.')->persistent()->send();
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
        return 'Karyawan berhasil diperbarui';
    }
}
