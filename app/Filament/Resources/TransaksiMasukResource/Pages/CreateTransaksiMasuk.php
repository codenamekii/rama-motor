<?php

namespace App\Filament\Resources\TransaksiMasukResource\Pages;

use App\Filament\Resources\TransaksiMasukResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTransaksiMasuk extends CreateRecord
{
    protected static string $resource = TransaksiMasukResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Transaksi masuk berhasil dibuat';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['no_transaksi'] = \App\Models\TransaksiMasuk::generateNoTransaksi();
        $data['user_id'] = Auth::id();

        // Set default status pembayaran
        if ($data['jenis_pembayaran'] === 'Cash' || $data['jenis_pembayaran'] === 'Transfer') {
            $data['status_pembayaran'] = 'Lunas';
        } else {
            $data['status_pembayaran'] = 'Belum Lunas';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Total akan dihitung otomatis di model melalui observer
        $this->record->hitungTotal();
    }
}
