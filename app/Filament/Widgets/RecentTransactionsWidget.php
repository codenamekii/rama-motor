<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\TransaksiKeluar;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Transaksi Terbaru Hari Ini';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(TransaksiKeluar::query()->whereDate('tanggal_transaksi', today())->latest())
            ->columns([
                Tables\Columns\TextColumn::make('no_transaksi')->label('No. Transaksi')->searchable()->size('sm')->weight('medium')->copyable(),

                Tables\Columns\TextColumn::make('created_at')->label('Waktu')->since()->size('sm')->color('gray'),

                Tables\Columns\TextColumn::make('nama_pelanggan_final')->label('Pelanggan')->searchable()->size('sm')->limit(20),

                Tables\Columns\TextColumn::make('total_bayar')->label('Total')->money('IDR')->size('sm')->weight('medium')->color('success'),

                Tables\Columns\TextColumn::make('jenis_pembayaran')->label('Bayar')->badge()->color(
                    fn(string $state): string => match ($state) {
                        'Cash' => 'success',
                        'Transfer' => 'info',
                        'Kredit' => 'warning',
                        'Debit' => 'primary',
                    },
                ),

                Tables\Columns\TextColumn::make('user.name')->label('Kasir')->size('sm')->limit(15),
            ])
            ->actions([Tables\Actions\Action::make('view')->label('Lihat')->icon('heroicon-m-eye')->url(fn($record) => route('filament.admin.resources.transaksi-keluar.view', $record))->openUrlInNewTab()])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->poll('10s')
            ->emptyStateHeading('Belum ada transaksi hari ini')
            ->emptyStateDescription('Transaksi yang dibuat hari ini akan muncul di sini');
    }
}
