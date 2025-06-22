<?php

namespace App\Filament\Resources\TransaksiKeluarResource\Pages;

use App\Filament\Resources\TransaksiKeluarResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Barang;
use Illuminate\Support\Facades\Auth;

class CreateTransaksiKeluar extends CreateRecord
{
    protected static string $resource = TransaksiKeluarResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Transaksi keluar berhasil dibuat';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['no_transaksi'] = \App\Models\TransaksiKeluar::generateNoTransaksi();
        $data['user_id'] = Auth::user() ? Auth::user()->id : null;

        // Set nama pelanggan jika pelanggan_id kosong
        if (!isset($data['pelanggan_id']) || !$data['pelanggan_id']) {
            $data['pelanggan_id'] = null;
            if (!isset($data['nama_pelanggan']) || !$data['nama_pelanggan']) {
                $data['nama_pelanggan'] = 'Umum';
            }
        }

        // Remove temporary fields
        unset($data['is_member']);
        unset($data['info_pelanggan']);
        unset($data['info_kembalian']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Total akan dihitung otomatis di model
        $this->record->hitungTotal();
    }

    protected function beforeCreate(): void
    {
        // Validate stock availability
        $details = $this->data['details'] ?? [];

        foreach ($details as $detail) {
            $barang = Barang::find($detail['barang_id']);
            if ($barang && $barang->stok < $detail['jumlah']) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('Stok tidak mencukupi')
                    ->body("Stok {$barang->nama} tidak mencukupi. Stok tersedia: {$barang->stok}")
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
    }
}
