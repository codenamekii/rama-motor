<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PemasokResource\Pages;
use App\Models\Pemasok;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;

class PemasokResource extends Resource
{
    protected static ?string $model = Pemasok::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Pemasok';

    protected static ?string $navigationGroup = 'Partner';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'pemasok';

    protected static ?string $pluralLabel = 'Pemasok';

    protected static ?string $modelLabel = 'Pemasok';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Perusahaan')
                ->description('Data perusahaan pemasok')
                ->schema([Forms\Components\TextInput::make('kode')->label('Kode Pemasok')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Kode akan digenerate otomatis')->columnSpan(1), Forms\Components\TextInput::make('nama_perusahaan')->label('Nama Perusahaan')->required()->maxLength(255)->placeholder('PT/CV/UD Nama Perusahaan')->columnSpan(2), Forms\Components\TextInput::make('npwp')->label('NPWP')->maxLength(50)->placeholder('00.000.000.0-000.000')->mask('99.999.999.9-999.999')])
                ->columns(3),

            Section::make('Kontak Person')
                ->description('Data kontak yang bisa dihubungi')
                ->schema([Forms\Components\TextInput::make('nama_kontak')->label('Nama Kontak')->maxLength(255)->placeholder('Nama lengkap kontak person'), Forms\Components\TextInput::make('jabatan_kontak')->label('Jabatan')->maxLength(100)->placeholder('Jabatan kontak person'), Forms\Components\TextInput::make('telepon')->label('Telepon Utama')->tel()->required()->maxLength(20)->placeholder('021-xxxx-xxxx'), Forms\Components\TextInput::make('telepon_2')->label('Telepon Alternatif')->tel()->maxLength(20)->placeholder('08xx-xxxx-xxxx'), Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255)->placeholder('email@perusahaan.com')->columnSpan(2)])
                ->columns(2),

            Section::make('Alamat')
                ->description('Alamat lengkap pemasok')
                ->schema([Forms\Components\Textarea::make('alamat')->label('Alamat Lengkap')->required()->rows(3)->maxLength(500)->placeholder('Jalan, Nomor, RT/RW')->columnSpanFull(), Forms\Components\TextInput::make('kota')->label('Kota/Kabupaten')->required()->maxLength(100)->placeholder('Nama kota atau kabupaten'), Forms\Components\TextInput::make('provinsi')->label('Provinsi')->required()->maxLength(100)->placeholder('Nama provinsi'), Forms\Components\TextInput::make('kode_pos')->label('Kode Pos')->maxLength(10)->placeholder('12345')])
                ->columns(3),

            Section::make('Informasi Bank')
                ->description('Data rekening untuk pembayaran')
                ->schema([
                    Forms\Components\Select::make('nama_bank')
                        ->label('Nama Bank')
                        ->options([
                            'BCA' => 'BCA',
                            'BNI' => 'BNI',
                            'BRI' => 'BRI',
                            'Mandiri' => 'Mandiri',
                            'CIMB Niaga' => 'CIMB Niaga',
                            'Danamon' => 'Danamon',
                            'Permata' => 'Permata',
                            'Bank Lainnya' => 'Bank Lainnya',
                        ])
                        ->searchable()
                        ->placeholder('Pilih bank'),

                    Forms\Components\TextInput::make('no_rekening')->label('No. Rekening')->maxLength(50)->placeholder('1234567890'),

                    Forms\Components\TextInput::make('atas_nama_rekening')->label('Atas Nama')->maxLength(255)->placeholder('Nama pemilik rekening'),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Informasi Tambahan')->schema([Forms\Components\Textarea::make('catatan')->label('Catatan')->rows(3)->maxLength(1000)->placeholder('Catatan tambahan tentang pemasok')->columnSpanFull(), Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true)->helperText('Nonaktifkan jika pemasok sudah tidak aktif')->inline()]),

            Section::make('Statistik & Hutang')
                ->schema([
                    Forms\Components\Placeholder::make('total_pembelian')->label('Total Pembelian')->content(fn(?Pemasok $record): string => $record ? 'Rp ' . number_format($record->total_pembelian, 0, ',', '.') : 'Rp 0'),

                    Forms\Components\Placeholder::make('jumlah_transaksi')->label('Jumlah Transaksi')->content(fn(?Pemasok $record): string => $record ? number_format($record->jumlah_transaksi) . ' transaksi' : '0 transaksi'),

                    Forms\Components\Placeholder::make('total_hutang')
                        ->label('Total Hutang')
                        ->content(fn(?Pemasok $record): string => $record ? 'Rp ' . number_format($record->total_hutang, 0, ',', '.') : 'Rp 0')
                        ->extraAttributes([
                            'class' => 'text-red-600 font-bold',
                        ]),
                ])
                ->columns(3)
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->sortable()->copyable()->weight('medium'),

                Tables\Columns\TextColumn::make('nama_perusahaan')->label('Nama Perusahaan')->searchable()->sortable()->weight('medium')->description(fn($record) => $record->kontak_lengkap)->wrap(),

                Tables\Columns\TextColumn::make('telepon')->label('Telepon')->searchable()->icon('heroicon-m-phone')->iconPosition('before')->copyable(),

                Tables\Columns\TextColumn::make('kota')->label('Kota')->searchable()->sortable()->badge()->color('gray'),

                Tables\Columns\TextColumn::make('total_pembelian')->label('Total Pembelian')->money('IDR')->sortable()->weight('medium')->color('success'),

                Tables\Columns\TextColumn::make('total_hutang')->label('Hutang')->getStateUsing(fn($record) => $record->total_hutang)->money('IDR')->sortable()->weight('medium')->color(fn($record) => $record->total_hutang > 0 ? 'danger' : 'gray')->icon(fn($record) => $record->total_hutang > 0 ? 'heroicon-o-exclamation-circle' : null),

                Tables\Columns\TextColumn::make('jumlah_transaksi')->label('Transaksi')->numeric()->sortable()->badge()->suffix(' x'),

                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif')->onColor('success')->offColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ])
                    ->placeholder('Semua Status'),

                Tables\Filters\SelectFilter::make('kota')->label('Kota')->options(fn() => Pemasok::distinct()->pluck('kota', 'kota')->filter()->toArray())->searchable()->multiple(),

                Tables\Filters\Filter::make('ada_hutang')
                    ->label('Ada Hutang')
                    ->query(
                        fn(Builder $query): Builder => $query->whereHas('transaksiMasuks', function ($q) {
                            $q->where('sisa_hutang', '>', 0);
                        }),
                    )
                    ->indicator('Ada Hutang'),

                Tables\Filters\Filter::make('pemasok_aktif')->label('Pemasok Aktif (30 hari)')->query(
                    fn(Builder $query): Builder => $query->whereHas('transaksiMasuks', function ($q) {
                        $q->where('tanggal_transaksi', '>=', now()->subDays(30));
                    }),
                ),
            ])
            ->actions([Tables\Actions\ViewAction::make()->slideOver(), Tables\Actions\EditAction::make(), Tables\Actions\Action::make('lihat_transaksi')->label('Transaksi')->icon('heroicon-o-document-text')->color('info')->url(fn($record) => route('filament.admin.resources.transaksi-masuks.index', ['tableFilters[pemasok_id][value]' => $record->id]))->openUrlInNewTab()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('kode', 'desc')
            ->poll('60s');
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
            'index' => Pages\ListPemasoks::route('/'),
            'create' => Pages\CreatePemasok::route('/create'),
            'edit' => Pages\EditPemasok::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'nama_perusahaan', 'nama_kontak', 'telepon'];
    }

    public static function getNavigationBadge(): ?string
    {
        $adaHutang = static::getModel()
            ::whereHas('transaksiMasuks', function ($q) {
                $q->where('sisa_hutang', '>', 0);
            })
            ->count();

        return $adaHutang > 0 ? $adaHutang : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pemasok dengan hutang';
    }
}
