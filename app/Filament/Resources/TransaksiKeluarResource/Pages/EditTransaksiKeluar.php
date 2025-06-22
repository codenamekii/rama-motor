<?php

namespace App\Filament\Resources\TransaksiKeluarResource\Pages;

use App\Filament\Resources\TransaksiKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTransaksiKeluar extends EditRecord
{
    protected static string $resource = TransaksiKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()->requiresConfirmation()->modalHeading('Hapus Transaksi')->modalDescription('Menghapus transaksi akan mengembalikan stok barang. Apakah Anda yakin?')->modalSubmitActionLabel('Ya, Hapus')->visible(fn() => Auth::user() && Auth::user()->role === 'super_admin')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Transaksi keluar berhasil diperbarui';
    }

    protected function afterSave(): void
    {
        // Recalculate total
        $this->record->hitungTotal();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set is_member based on pelanggan_id
        $data['is_member'] = !empty($data['pelanggan_id']);

        return $data;
    }
}
