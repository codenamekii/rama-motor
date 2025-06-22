<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiKeluarResource\Pages;
use App\Models\TransaksiKeluar;
use App\Models\Barang;
use App\Models\Pelanggan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;

class TransaksiKeluarResource extends Resource
{
    protected static ?string $model = TransaksiKeluar::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';

    protected static ?string $navigationLabel = 'Transaksi Keluar';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'transaksi-keluar';

    protected static ?string $pluralLabel = 'Transaksi Keluar';

    protected static ?string $modelLabel = 'Transaksi Keluar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Transaksi')
                ->description('Data transaksi penjualan ke pelanggan')
                ->schema([
                    Forms\Components\TextInput::make('no_transaksi')->label('No. Transaksi')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Nomor akan digenerate otomatis'),

                    Forms\Components\DatePicker::make('tanggal_transaksi')->label('Tanggal Transaksi')->required()->default(now())->maxDate(now())->displayFormat('d/m/Y'),

                    Forms\Components\Toggle::make('is_member')->label('Pelanggan Terdaftar')->default(false)->reactive()->helperText('Aktifkan jika pelanggan sudah terdaftar'),

                    Forms\Components\Select::make('pelanggan_id')
                        ->label('Pelanggan')
                        ->relationship('pelanggan', 'nama', fn($query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->visible(fn(Get $get) => $get('is_member'))
                        ->required(fn(Get $get) => $get('is_member'))
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $pelanggan = Pelanggan::find($state);
                                if ($pelanggan) {
                                    $set('info_pelanggan', "Telp: {$pelanggan->telepon} | Total: Rp " . number_format($pelanggan->total_pembelian, 0, ',', '.'));
                                }
                            }
                        })
                        ->createOptionForm([Forms\Components\TextInput::make('nama')->required()->maxLength(255), Forms\Components\Select::make('jenis_kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan']), Forms\Components\TextInput::make('telepon')->tel(), Forms\Components\Textarea::make('alamat')])
                        ->createOptionModalHeading('Tambah Pelanggan Baru'),

                    Forms\Components\TextInput::make('nama_pelanggan')->label('Nama Pelanggan')->maxLength(255)->visible(fn(Get $get) => !$get('is_member'))->required(fn(Get $get) => !$get('is_member'))->placeholder('Nama pelanggan umum'),

                    Forms\Components\Placeholder::make('info_pelanggan')->label('')->content(fn(Get $get): string => $get('info_pelanggan') ?? '-')->visible(fn(Get $get) => $get('is_member')),
                ])
                ->columns(2),

            Section::make('Detail Barang')->schema([
                Forms\Components\Repeater::make('details')
                    ->label('Daftar Barang')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('barang_id')
                            ->label('Barang')
                            ->options(function () {
                                return Barang::where('is_active', true)
                                    ->where('stok', '>', 0)
                                    ->get()
                                    ->mapWithKeys(function ($barang) {
                                        return [$barang->id => "{$barang->nama} (Stok: {$barang->stok})"];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $barang = Barang::find($state);
                                    if ($barang) {
                                        $set('harga_jual', $barang->harga_jual);
                                        $set('harga_beli', $barang->harga_beli);
                                        $set('max_qty', $barang->stok);
                                        $set('info_barang', "Stok: {$barang->stok} {$barang->satuanBarang->singkatan} | Margin: {$barang->margin}%");
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
                            ->maxValue(fn(Get $get) => $get('max_qty') ?? 999)
                            ->live()
                            ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calculateSubtotal($state, $get, $set))
                            ->helperText(fn(Get $get) => 'Max: ' . ($get('max_qty') ?? 0)),

                        Forms\Components\TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calculateSubtotal($get('jumlah'), $get, $set))
                            ->columnSpan(2),

                        Forms\Components\Hidden::make('harga_beli'),
                        Forms\Components\Hidden::make('max_qty'),

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

                        Forms\Components\Placeholder::make('info_barang')->label('')->content(fn(Get $get): string => $get('info_barang') ?? '')->columnSpan(3),
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
                            'Debit' => 'Debit',
                        ])
                        ->default('Cash')
                        ->required()
                        ->reactive(),

                    Forms\Components\DatePicker::make('tanggal_jatuh_tempo')->label('Tanggal Jatuh Tempo')->displayFormat('d/m/Y')->minDate(now())->visible(fn(Get $get) => $get('jenis_pembayaran') === 'Kredit')->required(fn(Get $get) => $get('jenis_pembayaran') === 'Kredit'),

                    Forms\Components\TextInput::make('diskon_persen')->label('Diskon %')->numeric()->default(0)->suffix('%')->minValue(0)->maxValue(100),

                    Forms\Components\TextInput::make('ppn_persen')->label('PPN %')->numeric()->default(0)->suffix('%')->minValue(0)->maxValue(100)->helperText('Isi 0 jika tidak ada PPN'),

                    Forms\Components\TextInput::make('biaya_lain')->label('Biaya Lain')->numeric()->prefix('Rp')->default(0)->helperText('Biaya kirim, handling, dll'),

                    Forms\Components\Placeholder::make('total_harga')->label('Total Harga')->content(fn($record): string => $record ? 'Rp ' . number_format($record->total_harga, 0, ',', '.') : 'Rp 0'),

                    Forms\Components\Placeholder::make('total_bayar')
                        ->label('Total Bayar')
                        ->content(fn($record): string => $record ? 'Rp ' . number_format($record->total_bayar, 0, ',', '.') : 'Rp 0')
                        ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600']),

                    Forms\Components\TextInput::make('jumlah_dibayar')
                        ->label('Jumlah Dibayar')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Get $get, Set $set, $record) {
                            if ($get('jenis_pembayaran') === 'Cash' && $record) {
                                $kembalian = $state - $record->total_bayar;
                                $set('info_kembalian', $kembalian >= 0 ? 'Kembalian: Rp ' . number_format($kembalian, 0, ',', '.') : 'Kurang: Rp ' . number_format(abs($kembalian), 0, ',', '.'));
                            }
                        })
                        ->helperText(fn(Get $get) => $get('jenis_pembayaran') === 'Cash' ? 'Masukkan uang yang diterima' : 'Masukkan jumlah yang dibayar'),

                    Forms\Components\Placeholder::make('info_kembalian')->label('')->content(fn(Get $get): string => $get('info_kembalian') ?? '')->visible(fn(Get $get) => $get('jenis_pembayaran') === 'Cash'),
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
        $harga = $get('harga_jual') ?? 0;
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

                Tables\Columns\TextColumn::make('nama_pelanggan_final')
                    ->label('Pelanggan')
                    ->getStateUsing(fn($record) => $record->nama_pelanggan_final)
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                $q->where('nama_pelanggan', 'like', "%{$search}%")->orWhereHas('pelanggan', function ($q) use ($search) {
                                    $q->where('nama', 'like', "%{$search}%");
                                });
                            });
                        },
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('jenis_pembayaran')->label('Pembayaran')->badge()->color(
                    fn(string $state): string => match ($state) {
                        'Cash' => 'success',
                        'Transfer' => 'info',
                        'Kredit' => 'warning',
                        'Debit' => 'primary',
                    },
                ),

                Tables\Columns\TextColumn::make('total_bayar')->label('Total')->money('IDR')->sortable()->weight('medium')->color('primary'),

                Tables\Columns\BadgeColumn::make('status_pembayaran')
                    ->label('Status')
                    ->colors([
                        'success' => 'Lunas',
                        'warning' => 'Sebagian',
                        'danger' => 'Belum Lunas',
                    ]),

                Tables\Columns\TextColumn::make('total_laba')->label('Laba')->getStateUsing(fn($record) => $record->total_laba)->money('IDR')->sortable()->color('success')->weight('medium')->visible(fn() => Auth::user() && Auth::user()->role === 'super_admin'),

                Tables\Columns\TextColumn::make('user.name')->label('Kasir')->searchable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pelanggan_id')->label('Pelanggan')->relationship('pelanggan', 'nama')->searchable()->preload(),

                Tables\Filters\SelectFilter::make('jenis_pembayaran')
                    ->label('Jenis Pembayaran')
                    ->options([
                        'Cash' => 'Cash',
                        'Transfer' => 'Transfer',
                        'Kredit' => 'Kredit',
                        'Debit' => 'Debit',
                    ]),

                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->label('Status')
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

                Tables\Filters\Filter::make('hari_ini')->label('Hari Ini')->query(fn(Builder $query): Builder => $query->whereDate('tanggal_transaksi', today())),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()->visible(fn($record) => $record->status_pembayaran !== 'Lunas'), Tables\Actions\Action::make('print')->label('Print')->icon('heroicon-o-printer')->color('gray')->url(fn($record) => route('transaksi-keluar.print', $record))->openUrlInNewTab()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Implement export logic
                        }),
                    Tables\Actions\DeleteBulkAction::make()->visible(fn() => Auth::user() && Auth::user()->role === 'super_admin'),
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
            'index' => Pages\ListTransaksiKeluars::route('/'),
            'create' => Pages\CreateTransaksiKeluar::route('/create'),
            'view' => Pages\ViewTransaksiKeluar::route('/{record}'),
            'edit' => Pages\EditTransaksiKeluar::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $hari_ini = static::getModel()::whereDate('tanggal_transaksi', today())->count();
        return $hari_ini > 0 ? $hari_ini : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Transaksi hari ini';
    }
}
