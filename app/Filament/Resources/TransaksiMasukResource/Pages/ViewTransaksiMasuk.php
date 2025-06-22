<?php

namespace App\Filament\Resources\TransaksiMasukResource\Pages;

use App\Filament\Resources\TransaksiMasukResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewTransaksiMasuk extends ViewRecord
{
    protected static string $resource = TransaksiMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('print')->label('Print')->icon('heroicon-o-printer')->color('gray')->url(fn() => route('transaksi-masuk.print', $this->record))->openUrlInNewTab(),
            Actions\Action::make('bayar')
                ->label('Bayar')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->visible(fn() => $this->record->status_pembayaran !== 'Lunas')
                ->form([\Filament\Forms\Components\TextInput::make('jumlah_bayar')->label('Jumlah Bayar')->numeric()->prefix('Rp')->required()->default(fn() => $this->record->sisa_hutang)->helperText(fn() => 'Sisa hutang: Rp ' . number_format($this->record->sisa_hutang, 0, ',', '.'))])
                ->action(function (array $data) {
                    $this->record->bayar($data['jumlah_bayar']);
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Pembayaran berhasil')
                        ->body('Sisa hutang: Rp ' . number_format($this->record->sisa_hutang, 0, ',', '.'))
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informasi Transaksi')
                ->schema([
                    Infolists\Components\TextEntry::make('no_transaksi')->label('No. Transaksi')->weight('medium')->copyable(),

                    Infolists\Components\TextEntry::make('tanggal_transaksi')->label('Tanggal')->date('d F Y'),

                    Infolists\Components\TextEntry::make('pemasok.nama_perusahaan')->label('Pemasok'),

                    Infolists\Components\TextEntry::make('no_faktur_supplier')->label('No. Faktur Supplier'),

                    Infolists\Components\TextEntry::make('jenis_pembayaran')->label('Jenis Pembayaran')->badge(),

                    Infolists\Components\TextEntry::make('status_pembayaran')->label('Status')->badge()->color(
                        fn(string $state): string => match ($state) {
                            'Lunas' => 'success',
                            'Sebagian' => 'warning',
                            'Belum Lunas' => 'danger',
                        },
                    ),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Detail Barang')->schema([
                Infolists\Components\RepeatableEntry::make('details')
                    ->label('')
                    ->schema([Infolists\Components\TextEntry::make('barang.nama')->label('Nama Barang')->weight('medium'), Infolists\Components\TextEntry::make('jumlah')->label('Jumlah')->suffix(fn($record) => ' ' . $record->barang->satuanBarang->singkatan), Infolists\Components\TextEntry::make('harga_beli')->label('Harga')->money('IDR'), Infolists\Components\TextEntry::make('diskon_persen')->label('Diskon')->suffix('%'), Infolists\Components\TextEntry::make('subtotal')->label('Subtotal')->money('IDR')->weight('medium')])
                    ->columns(5),
            ]),

            Infolists\Components\Section::make('Ringkasan Pembayaran')
                ->schema([Infolists\Components\TextEntry::make('total_harga')->label('Total Harga')->money('IDR'), Infolists\Components\TextEntry::make('diskon_nominal')->label('Diskon')->money('IDR')->visible(fn($record) => $record->diskon_nominal > 0), Infolists\Components\TextEntry::make('ppn_nominal')->label('PPN')->money('IDR')->visible(fn($record) => $record->ppn_nominal > 0), Infolists\Components\TextEntry::make('biaya_lain')->label('Biaya Lain')->money('IDR')->visible(fn($record) => $record->biaya_lain > 0), Infolists\Components\TextEntry::make('total_bayar')->label('Total Bayar')->money('IDR')->weight('bold')->size('lg'), Infolists\Components\TextEntry::make('jumlah_dibayar')->label('Sudah Dibayar')->money('IDR'), Infolists\Components\TextEntry::make('sisa_hutang')->label('Sisa Hutang')->money('IDR')->color(fn($state) => $state > 0 ? 'danger' : 'success')->weight('medium')])
                ->columns(3),

            Infolists\Components\Section::make('Informasi Tambahan')
                ->schema([Infolists\Components\TextEntry::make('keterangan')->label('Keterangan')->columnSpanFull(), Infolists\Components\TextEntry::make('user.name')->label('Diinput oleh'), Infolists\Components\TextEntry::make('created_at')->label('Waktu Input')->dateTime('d/m/Y H:i')])
                ->columns(2)
                ->collapsible(),
        ]);
    }
}
