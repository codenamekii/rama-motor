<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Barang;
use Illuminate\Database\Eloquent\Builder;

class StokKritisWidget extends BaseWidget
{
    protected static ?string $heading = 'Barang Stok Kritis';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Barang::query()
                    ->where('is_active', true)
                    ->where(function (Builder $query) {
                        $query->where('stok', 0)->orWhereColumn('stok', '<=', 'stok_minimal');
                    }),
            )
            ->columns([Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->size('sm'), Tables\Columns\TextColumn::make('nama')->label('Nama Barang')->searchable()->wrap()->size('sm')->weight('medium'), Tables\Columns\TextColumn::make('stok')->label('Stok')->badge()->color(fn($state) => $state === 0 ? 'danger' : 'warning')->formatStateUsing(fn($state, $record) => $state . ' ' . $record->satuanBarang->singkatan), Tables\Columns\TextColumn::make('stok_minimal')->label('Min')->badge()->color('gray')->formatStateUsing(fn($state, $record) => $state . ' ' . $record->satuanBarang->singkatan), Tables\Columns\TextColumn::make('stok_status.label')->label('Status')->badge()->color(fn($record) => $record->stok_status['color'])])
            ->actions([Tables\Actions\Action::make('view')->label('Lihat')->icon('heroicon-m-eye')->url(fn($record) => route('filament.admin.resources.barangs.edit', $record))->openUrlInNewTab()])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->poll('30s');
    }
}
