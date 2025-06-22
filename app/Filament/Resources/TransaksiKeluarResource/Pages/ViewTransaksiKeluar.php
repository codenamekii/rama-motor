<?php

namespace App\Filament\Resources\TransaksiKeluarResource\Pages;

use App\Filament\Resources\TransaksiKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;

class ViewTransaksiKeluar extends ViewRecord
{
    protected static string $resource = TransaksiKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn() => $this->record->status_pembayaran !== 'Lunas'),
            Actions\Action::make('print')->label('Print Struk')->icon('heroicon-o-printer')->color('gray')->url(fn() => route('transaksi-keluar.print', $this->record))->openUrlInNewTab(),
            Actions\Action::make('bayar')
                ->label('Bayar')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->visible(fn() => $this->record->status_pembayaran !== 'Lunas' && $this->record->jenis_pembayaran === 'Kredit')
                ->form([\Filament\Forms\Components\TextInput::make('jumlah_bayar')->label('Jumlah Bayar')->numeric()->prefix('Rp')->required()->default(fn() => $this->record->sisa_piutang)->helperText(fn() => 'Sisa piutang: Rp ' . number_format($this->record->sisa_piutang, 0, ',', '.'))])
                ->action(function (array $data) {
                    $this->record->bayar($data['jumlah_bayar']);
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Pembayaran berhasil')
                        ->body('Sisa piutang: Rp ' . number_format($this->record->sisa_piutang, 0, ',', '.'))
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

                    Infolists\Components\TextEntry::make('nama_pelanggan_final')->label('Pelanggan'),

                    Infolists\Components\TextEntry::make('jenis_pembayaran')->label('Jenis Pembayaran')->badge()->color(
                        fn(string $state): string => match ($state) {
                            'Cash' => 'success',
                            'Transfer' => 'info',
                            'Kredit' => 'warning',
                            'Debit' => 'primary',
                        },
                    ),

                    Infolists\Components\TextEntry::make('status_pembayaran')->label('Status')->badge()->color(
                        fn(string $state): string => match ($state) {
                            'Lunas' => 'success',
                            'Sebagian' => 'warning',
                            'Belum Lunas' => 'danger',
                        },
                    ),

                    Infolists\Components\TextEntry::make('user.name')->label('Kasir'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Detail Barang')->schema([
                Infolists\Components\RepeatableEntry::make('details')
                    ->label('')
                    ->schema([Infolists\Components\TextEntry::make('barang.nama')->label('Nama Barang')->weight('medium'), Infolists\Components\TextEntry::make('jumlah')->label('Jumlah')->suffix(fn($record) => ' ' . $record->barang->satuanBarang->singkatan), Infolists\Components\TextEntry::make('harga_jual')->label('Harga')->money('IDR'), Infolists\Components\TextEntry::make('diskon_persen')->label('Diskon')->suffix('%'), Infolists\Components\TextEntry::make('subtotal')->label('Subtotal')->money('IDR')->weight('medium')])
                    ->columns(5),
            ]),

            Infolists\Components\Section::make('Ringkasan Pembayaran')
                ->schema([
                    Infolists\Components\TextEntry::make('total_harga')->label('Total Harga')->money('IDR'),

                    Infolists\Components\TextEntry::make('diskon_nominal')->label('Diskon')->money('IDR')->visible(fn($record) => $record->diskon_nominal > 0),

                    Infolists\Components\TextEntry::make('ppn_nominal')->label('PPN')->money('IDR')->visible(fn($record) => $record->ppn_nominal > 0),

                    Infolists\Components\TextEntry::make('biaya_lain')->label('Biaya Lain')->money('IDR')->visible(fn($record) => $record->biaya_lain > 0),

                    Infolists\Components\TextEntry::make('total_bayar')->label('Total Bayar')->money('IDR')->weight('bold')->size('lg'),

                    Infolists\Components\TextEntry::make('jumlah_dibayar')->label('Dibayar')->money('IDR'),

                    Infolists\Components\TextEntry::make('kembalian')->label('Kembalian')->money('IDR')->visible(fn($record) => $record->kembalian > 0)->color('success'),

                    Infolists\Components\TextEntry::make('sisa_piutang')->label('Sisa Piutang')->money('IDR')->visible(fn($record) => $record->sisa_piutang > 0)->color('danger')->weight('medium'),

                    Infolists\Components\TextEntry::make('total_laba')->label('Laba')->money('IDR')->color('success')->weight('medium')->visible(fn() => Auth::user() && Auth::user()->role === 'super_admin'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Informasi Tambahan')
                ->schema([Infolists\Components\TextEntry::make('keterangan')->label('Keterangan')->columnSpanFull(), Infolists\Components\TextEntry::make('created_at')->label('Waktu Input')->dateTime('d/m/Y H:i')])
                ->columns(2)
                ->collapsible()
                ->collapsed(fn($record) => empty($record->keterangan)),
        ]);
    }
}
