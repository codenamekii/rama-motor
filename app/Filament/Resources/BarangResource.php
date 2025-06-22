<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangResource\Pages;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\SatuanBarang;
use App\Models\MerkBarang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Barang';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'barang';

    protected static ?string $pluralLabel = 'Barang';

    protected static ?string $modelLabel = 'Barang';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Barang')
                ->description('Data utama barang')
                ->schema([
                    Forms\Components\TextInput::make('kode')->label('Kode Barang')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Kode akan digenerate otomatis')->columnSpan(1),

                    Forms\Components\TextInput::make('nama')->label('Nama Barang')->required()->maxLength(255)->placeholder('Contoh: Oli Mesin Federal 10W-40')->live(onBlur: true)->columnSpan(2),

                    Forms\Components\Select::make('jenis_barang_id')
                        ->label('Jenis Barang')
                        ->relationship('jenisBarang', 'nama', fn($query) => $query->where('is_active', true))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([Forms\Components\TextInput::make('nama')->required()->maxLength(255), Forms\Components\Textarea::make('deskripsi')->maxLength(500)])
                        ->createOptionModalHeading('Tambah Jenis Barang Baru'),

                    Forms\Components\Select::make('satuan_barang_id')
                        ->label('Satuan')
                        ->relationship('satuanBarang', 'nama', fn($query) => $query->where('is_active', true))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->nama} ({$record->singkatan})")
                        ->createOptionForm([Forms\Components\TextInput::make('nama')->required()->maxLength(50), Forms\Components\TextInput::make('singkatan')->required()->maxLength(10)])
                        ->createOptionModalHeading('Tambah Satuan Baru'),

                    Forms\Components\Select::make('merk_barang_id')
                        ->label('Merk')
                        ->relationship('merkBarang', 'nama', fn($query) => $query->where('is_active', true))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('nama')->required()->maxLength(255),
                            Forms\Components\Select::make('negara_asal')->options([
                                'Indonesia' => 'Indonesia',
                                'Jepang' => 'Jepang',
                                'China' => 'China',
                                'Lainnya' => 'Lainnya',
                            ]),
                        ])
                        ->createOptionModalHeading('Tambah Merk Baru'),

                    Forms\Components\Textarea::make('deskripsi')->label('Deskripsi')->rows(3)->maxLength(1000)->placeholder('Deskripsi detail tentang barang')->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('Harga & Stok')
                ->description('Informasi harga dan stok barang')
                ->schema([
                    Forms\Components\TextInput::make('harga_beli')
                        ->label('Harga Beli')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, Forms\Set $set, Get $get) => $set('margin', $get('harga_jual') && $state > 0 ? round((($get('harga_jual') - $state) / $state) * 100, 2) : 0)),

                    Forms\Components\TextInput::make('harga_jual')
                        ->label('Harga Jual')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, Forms\Set $set, Get $get) => $set('margin', $get('harga_beli') && $get('harga_beli') > 0 ? round((($state - $get('harga_beli')) / $get('harga_beli')) * 100, 2) : 0)),

                    Forms\Components\TextInput::make('margin')->label('Margin (%)')->disabled()->dehydrated(false)->suffix('%')->helperText('Keuntungan dalam persen'),

                    Forms\Components\TextInput::make('stok')->label('Stok Saat Ini')->numeric()->required()->default(0)->suffix('unit')->disabled(fn(?Barang $record) => $record !== null)->helperText(fn(?Barang $record) => $record ? 'Stok diupdate melalui transaksi' : 'Stok awal'),

                    Forms\Components\TextInput::make('stok_minimal')->label('Stok Minimal')->numeric()->required()->default(0)->suffix('unit')->helperText('Peringatan jika stok dibawah nilai ini'),

                    Forms\Components\TextInput::make('lokasi_penyimpanan')->label('Lokasi Penyimpanan')->maxLength(255)->placeholder('Contoh: Rak A1, Gudang Utama'),

                    Forms\Components\FileUpload::make('gambar')->label('Foto Barang')->image()->imageEditor()->maxSize(2048)->directory('barang')->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true)->helperText('Nonaktifkan jika barang tidak dijual lagi')->inline()->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('Informasi Tambahan')
                ->schema([
                    Forms\Components\Placeholder::make('stok_status')
                        ->label('Status Stok')
                        ->content(function (?Barang $record): string {
                            if (!$record) {
                                return '-';
                            }

                            $status = $record->stok_status;
                            $color = match ($status['color']) {
                                'danger' => 'red',
                                'warning' => 'yellow',
                                'success' => 'green',
                                default => 'gray',
                            };

                            return "<span class='text-{$color}-600 font-semibold'>{$status['label']}</span>";
                        })
                        ->hint(fn(?Barang $record) => $record ? "Stok: {$record->stok} / Min: {$record->stok_minimal}" : null),

                    Forms\Components\Placeholder::make('nilai_stok')->label('Nilai Stok')->content(fn(?Barang $record): string => $record ? 'Rp ' . number_format($record->nilai_stok, 0, ',', '.') : 'Rp 0')->helperText('Stok × Harga Beli'),

                    Forms\Components\Placeholder::make('created_at')->label('Dibuat pada')->content(fn(?Barang $record): string => $record ? $record->created_at->format('d/m/Y H:i') : '-'),
                ])
                ->columns(3)
                ->collapsible()
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->sortable()->copyable()->weight('medium'),

                Tables\Columns\TextColumn::make('gambar')
                    ->label('Foto')
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '<div class="w-10 h-10 rounded-full bg-gray-300"></div>';
                        }
                        return '<img src="' . url('storage/' . $state) . '" class="w-10 h-10 rounded-full object-cover">';
                    }),

                Tables\Columns\TextColumn::make('nama')->label('Nama Barang')->searchable()->sortable()->weight('medium')->description(fn($record) => $record->jenisBarang->nama . ' - ' . $record->merkBarang->nama)->wrap(),

                Tables\Columns\TextColumn::make('satuanBarang.singkatan')->label('Satuan')->badge()->color('info'),

                Tables\Columns\TextColumn::make('harga_beli')->label('Harga Beli')->money('IDR')->sortable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('harga_jual')->label('Harga Jual')->money('IDR')->sortable()->weight('medium'),

                Tables\Columns\TextColumn::make('margin')
                    ->label('Margin')
                    ->getStateUsing(fn($record) => $record->margin)
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->badge()
                    ->color(
                        fn($state) => match (true) {
                            $state >= 30 => 'success',
                            $state >= 20 => 'info',
                            $state >= 10 => 'warning',
                            default => 'danger',
                        },
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(
                        fn($record) => match ($record->stok_status['color']) {
                            'danger' => 'danger',
                            'warning' => 'warning',
                            'success' => 'success',
                            default => 'gray',
                        },
                    )
                    ->formatStateUsing(fn($state, $record) => $state . ' ' . $record->satuanBarang->singkatan),

                Tables\Columns\TextColumn::make('stok_minimal')->label('Min')->numeric()->sortable()->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif')->onColor('success')->offColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_barang_id')->label('Jenis Barang')->relationship('jenisBarang', 'nama', fn($query) => $query->where('is_active', true))->multiple()->preload(),

                Tables\Filters\SelectFilter::make('merk_barang_id')->label('Merk')->relationship('merkBarang', 'nama', fn($query) => $query->where('is_active', true))->multiple()->preload(),

                Tables\Filters\Filter::make('stok_habis')->label('Stok Habis')->query(fn(Builder $query): Builder => $query->where('stok', 0)),

                Tables\Filters\Filter::make('stok_menipis')->label('Stok Menipis')->query(fn(Builder $query): Builder => $query->whereColumn('stok', '<=', 'stok_minimal')->where('stok', '>', 0)),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ]),
            ])
            ->actions([Tables\Actions\ViewAction::make()->slideOver(), Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('kode', 'desc')
            ->poll('30s');
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
            'index' => Pages\ListBarangs::route('/'),
            'create' => Pages\CreateBarang::route('/create'),
            'edit' => Pages\EditBarang::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'nama'];
    }

    public static function getNavigationBadge(): ?string
    {
        $lowStock = static::getModel()::lowStock()->count();
        return $lowStock > 0 ? $lowStock : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Barang dengan stok menipis';
    }
}
