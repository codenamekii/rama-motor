<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelangganResource\Pages;
use App\Models\Pelanggan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;

class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $navigationGroup = 'Partner';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'pelanggan';

    protected static ?string $pluralLabel = 'Pelanggan';

    protected static ?string $modelLabel = 'Pelanggan';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Pelanggan')
                ->description('Data pribadi pelanggan')
                ->schema([
                    Forms\Components\TextInput::make('kode')->label('Kode Pelanggan')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Kode akan digenerate otomatis'),

                    Forms\Components\TextInput::make('nama')->label('Nama Lengkap')->required()->maxLength(255)->placeholder('Masukkan nama lengkap pelanggan'),

                    Forms\Components\Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ])
                        ->placeholder('Pilih jenis kelamin'),

                    Forms\Components\TextInput::make('telepon')->label('No. Telepon')->tel()->maxLength(20)->placeholder('08xx-xxxx-xxxx'),

                    Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255)->placeholder('email@example.com'),

                    Forms\Components\DatePicker::make('tanggal_bergabung')->label('Tanggal Bergabung')->default(now())->maxDate(now())->displayFormat('d/m/Y'),
                ])
                ->columns(2),

            Section::make('Alamat')
                ->description('Alamat lengkap pelanggan')
                ->schema([Forms\Components\Textarea::make('alamat')->label('Alamat Lengkap')->rows(3)->maxLength(500)->placeholder('Jalan, RT/RW, Kelurahan')->columnSpanFull(), Forms\Components\TextInput::make('kota')->label('Kota/Kabupaten')->maxLength(100)->placeholder('Nama kota atau kabupaten'), Forms\Components\TextInput::make('provinsi')->label('Provinsi')->maxLength(100)->placeholder('Nama provinsi'), Forms\Components\TextInput::make('kode_pos')->label('Kode Pos')->maxLength(10)->placeholder('12345')])
                ->columns(3),

            Section::make('Informasi Tambahan')->schema([Forms\Components\Textarea::make('catatan')->label('Catatan')->rows(3)->maxLength(1000)->placeholder('Catatan tambahan tentang pelanggan')->columnSpanFull(), Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true)->helperText('Nonaktifkan jika pelanggan sudah tidak aktif')->inline()]),

            Section::make('Statistik')
                ->schema([Forms\Components\Placeholder::make('total_pembelian')->label('Total Pembelian')->content(fn(?Pelanggan $record): string => $record ? 'Rp ' . number_format($record->total_pembelian, 0, ',', '.') : 'Rp 0'), Forms\Components\Placeholder::make('jumlah_transaksi')->label('Jumlah Transaksi')->content(fn(?Pelanggan $record): string => $record ? number_format($record->jumlah_transaksi) . ' transaksi' : '0 transaksi'), Forms\Components\Placeholder::make('rata_rata')->label('Rata-rata per Transaksi')->content(fn(?Pelanggan $record): string => $record && $record->jumlah_transaksi > 0 ? 'Rp ' . number_format($record->total_pembelian / $record->jumlah_transaksi, 0, ',', '.') : 'Rp 0')])
                ->columns(3)
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->sortable()->copyable()->weight('medium'),

                Tables\Columns\TextColumn::make('nama')->label('Nama Pelanggan')->searchable()->sortable()->weight('medium')->description(fn($record) => $record->telepon),

                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('L/P')
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            'L' => 'info',
                            'P' => 'danger',
                            default => 'gray',
                        },
                    )
                    ->formatStateUsing(fn(string $state): string => $state),

                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->toggleable()->icon('heroicon-m-envelope')->iconPosition('before')->copyable(),

                Tables\Columns\TextColumn::make('kota')->label('Kota')->searchable()->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('total_pembelian')->label('Total Pembelian')->money('IDR')->sortable()->weight('medium')->color('success'),

                Tables\Columns\TextColumn::make('jumlah_transaksi')->label('Jml Transaksi')->numeric()->sortable()->badge()->color(
                    fn(int $state): string => match (true) {
                        $state >= 50 => 'success',
                        $state >= 20 => 'info',
                        $state >= 5 => 'warning',
                        default => 'gray',
                    },
                ),

                Tables\Columns\TextColumn::make('tanggal_bergabung')->label('Bergabung')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),

                Tables\Filters\SelectFilter::make('kota')->label('Kota')->options(fn() => Pelanggan::distinct()->pluck('kota', 'kota')->filter()->toArray())->searchable(),

                Tables\Filters\Filter::make('pelanggan_vip')->label('Pelanggan VIP')->query(fn(Builder $query): Builder => $query->where('total_pembelian', '>=', 10000000)),

                Tables\Filters\Filter::make('pelanggan_baru')->label('Pelanggan Baru (30 hari)')->query(fn(Builder $query): Builder => $query->where('tanggal_bergabung', '>=', now()->subDays(30))),
            ])
            ->actions([Tables\Actions\ViewAction::make()->slideOver(), Tables\Actions\EditAction::make(), Tables\Actions\Action::make('lihat_transaksi')->label('Transaksi')->icon('heroicon-o-shopping-cart')->color('info')->url(fn($record) => route('filament.admin.resources.transaksi-keluar.index', ['tableFilters[pelanggan_id][value]' => $record->id]))->openUrlInNewTab()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\BulkAction::make('activate')->label('Aktifkan')->icon('heroicon-o-check')->color('success')->action(fn($records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion()->requiresConfirmation(), Tables\Actions\BulkAction::make('deactivate')->label('Nonaktifkan')->icon('heroicon-o-x-mark')->color('danger')->action(fn($records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion()->requiresConfirmation(), Tables\Actions\DeleteBulkAction::make()])])
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
            'index' => Pages\ListPelanggans::route('/'),
            'create' => Pages\CreatePelanggan::route('/create'),
            'edit' => Pages\EditPelanggan::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'nama', 'telepon', 'email'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
