<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiMasukResource\Pages;
use App\Models\TransaksiMasuk;
use App\Models\Barang;
use App\Models\Pemasok;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;

class TransaksiMasukResource extends Resource
{
    protected static ?string $model = TransaksiMasuk::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square';

    protected static ?string $navigationLabel = 'Transaksi Masuk';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'transaksi-masuk';

    protected static ?string $pluralLabel = 'Transaksi Masuk';

    protected static ?string $modelLabel = 'Transaksi Masuk';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Transaksi')
                ->description('Data transaksi pembelian dari pemasok')
                ->schema([
                    Forms\Components\TextInput::make('no_transaksi')->label('No. Transaksi')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Nomor akan digenerate otomatis'),

                    Forms\Components\DatePicker::make('tanggal_transaksi')->label('Tanggal Transaksi')->required()->default(now())->maxDate(now())->displayFormat('d/m/Y'),

                    Forms\Components\Select::make('pemasok_id')
                        ->label('Pemasok')
                        ->relationship('pemasok', 'nama_perusahaan', fn($query) => $query->where('is_active', true))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $pemasok = Pemasok::find($state);
                                if ($pemasok) {
                                    $set('info_pemasok', "Telp: {$pemasok->telepon} | Hutang: Rp " . number_format($pemasok->total_hutang, 0, ',', '.'));
                                }
                            }
                        })
                        ->createOptionForm([Forms\Components\TextInput::make('nama_perusahaan')->required()->maxLength(255), Forms\Components\TextInput::make('telepon')->required()->tel(), Forms\Components\Textarea::make('alamat')->required(), Forms\Components\TextInput::make('kota')->required(), Forms\Components\TextInput::make('provinsi')->required()])
                        ->createOptionModalHeading('Tambah Pemasok Baru'),

                    Forms\Components\Placeholder::make('info_pemasok')->label('')->content(fn(Get $get): string => $get('info_pemasok') ?? '-'),

                    Forms\Components\TextInput::make('no_faktur_supplier')->label('No. Faktur Supplier')->maxLength(50)->placeholder('Nomor faktur dari supplier'),
                ])
                ->columns(2),

            Section::make('Detail Barang')->schema([
                Forms\Components\Repeater::make('details')
                    ->label('Daftar Barang')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('barang_id')
                            ->label('Barang')
                            ->options(Barang::where('is_active', true)->get()->pluck('nama', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $barang = Barang::find($state);
                                    if ($barang) {
                                        $set('harga_beli', $barang->harga_beli);
                                        $set('info_barang', "Stok: {$barang->stok} {$barang->satuanBarang->singkatan}");
                                    }
                                }
                            })
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calculateSubtotal($state, $get, $set)),

                        Forms\Components\TextInput::make('harga_beli')
                            ->label('Harga Beli')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calculateSubtotal($get('jumlah'), $get, $set))
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('diskon_persen')
                            ->label('Diskon %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->live()
                            ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calculateSubtotal($get('jumlah'), $get, $set)),

                        Forms\Components\TextInput::make('subtotal')->label('Subtotal')->prefix('Rp')->disabled()->dehydrated()->numeric()->columnSpan(2),

                        Forms\Components\DatePicker::make('tanggal_expired')->label('Tgl Expired')->displayFormat('d/m/Y')->minDate(now()),

                        Forms\Components\TextInput::make('no_batch')->label('No. Batch')->maxLength(50),

                        Forms\Components\Placeholder::make('info_barang')->label('')->content(fn(Get $get): string => $get('info_barang') ?? '')->columnSpan(2),
                    ])
                    ->columns(12)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Barang')
                    ->reorderable()
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn(array $state): ?string => isset($state['barang_id']) && $state['barang_id'] ? Barang::find($state['barang_id'])?->nama . ' - ' . ($state['jumlah'] ?? 0) . ' unit' : null),
            ]),

            Section::make('Pembayaran')
                ->schema([
                    Forms\Components\Select::make('jenis_pembayaran')
                        ->label('Jenis Pembayaran')
                        ->options([
                            'Cash' => 'Cash',
                            'Transfer' => 'Transfer',
                            'Kredit' => 'Kredit',
                        ])
                        ->default('Cash')
                        ->required()
                        ->reactive(),

                    Forms\Components\DatePicker::make('tanggal_jatuh_tempo')->label('Tanggal Jatuh Tempo')->displayFormat('d/m/Y')->minDate(now())->visible(fn(Get $get) => $get('jenis_pembayaran') === 'Kredit')->required(fn(Get $get) => $get('jenis_pembayaran') === 'Kredit'),

                    Forms\Components\TextInput::make('diskon_persen')->label('Diskon %')->numeric()->default(0)->suffix('%')->minValue(0)->maxValue(100),

                    Forms\Components\TextInput::make('ppn_persen')->label('PPN %')->numeric()->default(11)->suffix('%')->minValue(0)->maxValue(100),

                    Forms\Components\TextInput::make('biaya_lain')->label('Biaya Lain')->numeric()->prefix('Rp')->default(0)->helperText('Biaya kirim, handling, dll'),

                    Forms\Components\Placeholder::make('total_harga')->label('Total Harga')->content(fn($record): string => $record ? 'Rp ' . number_format($record->total_harga, 0, ',', '.') : 'Rp 0'),

                    Forms\Components\Placeholder::make('total_bayar')
                        ->label('Total Bayar')
                        ->content(fn($record): string => $record ? 'Rp ' . number_format($record->total_bayar, 0, ',', '.') : 'Rp 0')
                        ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600']),

                    Forms\Components\TextInput::make('jumlah_dibayar')->label('Jumlah Dibayar')->numeric()->prefix('Rp')->default(0)->required()->helperText(fn(Get $get) => $get('jenis_pembayaran') === 'Kredit' ? 'Bisa diisi 0 untuk hutang penuh' : 'Harus dibayar penuh untuk Cash/Transfer'),
                ])
                ->columns(2),

            Section::make('Keterangan')
                ->schema([Forms\Components\Textarea::make('keterangan')->label('Keterangan')->rows(3)->maxLength(1000)->placeholder('Catatan tambahan untuk transaksi ini')])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    protected static function calculateSubtotal($jumlah, Get $get, Set $set): void
    {
        $harga = $get('harga_beli') ?? 0;
        $diskon = $get('diskon_persen') ?? 0;

        $subtotal = $jumlah * $harga;
        $diskonNominal = $subtotal * ($diskon / 100);
        $subtotal = $subtotal - $diskonNominal;

        $set('diskon_nominal', $diskonNominal);
        $set('subtotal', $subtotal);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_transaksi')->label('No. Transaksi')->searchable()->sortable()->copyable()->weight('medium'),

                Tables\Columns\TextColumn::make('tanggal_transaksi')->label('Tanggal')->date('d/m/Y')->sortable(),

                Tables\Columns\TextColumn::make('pemasok.nama_perusahaan')->label('Pemasok')->searchable()->sortable()->wrap(),

                Tables\Columns\TextColumn::make('no_faktur_supplier')->label('No. Faktur')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('total_bayar')->label('Total')->money('IDR')->sortable()->weight('medium')->color('primary'),

                Tables\Columns\BadgeColumn::make('status_pembayaran')
                    ->label('Status')
                    ->colors([
                        'success' => 'Lunas',
                        'warning' => 'Sebagian',
                        'danger' => 'Belum Lunas',
                    ]),

                Tables\Columns\TextColumn::make('sisa_hutang')->label('Sisa Hutang')->money('IDR')->sortable()->color(fn($state) => $state > 0 ? 'danger' : 'gray')->weight(fn($state) => $state > 0 ? 'medium' : null),

                Tables\Columns\IconColumn::make('is_overdue')->label('Jatuh Tempo')->getStateUsing(fn($record) => $record->isOverdue())->boolean()->trueIcon('heroicon-o-exclamation-triangle')->falseIcon('heroicon-o-check-circle')->trueColor('danger')->falseColor('gray'),

                Tables\Columns\TextColumn::make('user.name')->label('Input By')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pemasok_id')->label('Pemasok')->relationship('pemasok', 'nama_perusahaan')->searchable()->preload(),

                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options([
                        'Lunas' => 'Lunas',
                        'Sebagian' => 'Sebagian',
                        'Belum Lunas' => 'Belum Lunas',
                    ]),

                Tables\Filters\Filter::make('tanggal_transaksi')
                    ->form([Forms\Components\DatePicker::make('dari')->label('Dari Tanggal'), Forms\Components\DatePicker::make('sampai')->label('Sampai Tanggal')])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['dari'], fn(Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '>=', $date))->when($data['sampai'], fn(Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '<=', $date));
                    }),

                Tables\Filters\Filter::make('jatuh_tempo')->label('Sudah Jatuh Tempo')->query(fn(Builder $query): Builder => $query->jatuhTempo()),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(), Tables\Actions\Action::make('print')->label('Print')->icon('heroicon-o-printer')->color('gray')->url(fn($record) => route('transaksi-masuk.print', $record))->openUrlInNewTab()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Implement export logic
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('no_transaksi', 'desc');
    }

    public static function getRelations(): array
    {
        return [
                //
            ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiMasuks::route('/'),
            'create' => Pages\CreateTransaksiMasuk::route('/create'),
            'view' => Pages\ViewTransaksiMasuk::route('/{record}'),
            'edit' => Pages\EditTransaksiMasuk::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $belumLunas = static::getModel()::where('status_pembayaran', '!=', 'Lunas')->count();
        return $belumLunas > 0 ? $belumLunas : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
