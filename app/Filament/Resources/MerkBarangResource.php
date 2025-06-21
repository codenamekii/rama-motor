<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerkBarangResource\Pages;
use App\Models\MerkBarang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MerkBarangResource extends Resource
{
    protected static ?string $model = MerkBarang::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Merk Barang';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'merk-barang';

    protected static ?string $pluralLabel = 'Merk Barang';

    protected static ?string $modelLabel = 'Merk Barang';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Merk Barang')
                ->description('Kelola data merk/brand barang')
                ->schema([
                    Forms\Components\TextInput::make('kode')->label('Kode Merk')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Kode akan digenerate otomatis'),

                    Forms\Components\TextInput::make('nama')->label('Nama Merk')->required()->maxLength(255)->placeholder('Contoh: Honda, Yamaha, Federal')->live(onBlur: true)->unique(ignoreRecord: true),

                    Forms\Components\Select::make('negara_asal')
                        ->label('Negara Asal')
                        ->options([
                            'Indonesia' => 'Indonesia',
                            'Jepang' => 'Jepang',
                            'Thailand' => 'Thailand',
                            'Malaysia' => 'Malaysia',
                            'China' => 'China',
                            'Korea Selatan' => 'Korea Selatan',
                            'Taiwan' => 'Taiwan',
                            'India' => 'India',
                            'Amerika Serikat' => 'Amerika Serikat',
                            'Jerman' => 'Jerman',
                            'Italia' => 'Italia',
                            'Lainnya' => 'Lainnya',
                        ])
                        ->searchable()
                        ->placeholder('Pilih negara asal'),

                    Forms\Components\Textarea::make('deskripsi')->label('Deskripsi')->rows(3)->maxLength(500)->placeholder('Deskripsi singkat tentang merk ini')->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true)->helperText('Nonaktifkan jika merk tidak digunakan lagi')->inline(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Statistik')
                ->schema([Forms\Components\Placeholder::make('jumlah_barang')->label('Jumlah Barang')->content(fn(?MerkBarang $record): string => $record ? number_format($record->jumlah_barang) . ' item' : '0 item'), Forms\Components\Placeholder::make('total_stok')->label('Total Stok')->content(fn(?MerkBarang $record): string => $record ? number_format($record->total_stok) . ' unit' : '0 unit'), Forms\Components\Placeholder::make('created_at')->label('Dibuat pada')->content(fn(?MerkBarang $record): string => $record ? $record->created_at->format('d/m/Y H:i') : '-')])
                ->columns(3)
                ->collapsible()
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->sortable()->copyable()->copyMessage('Kode berhasil disalin')->tooltip('Klik untuk menyalin'),

                Tables\Columns\TextColumn::make('nama')->label('Nama Merk')->searchable()->sortable()->weight('medium')->description(fn($record) => $record->negara_asal),

                Tables\Columns\TextColumn::make('negara_asal')
                    ->label('Negara Asal')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            'Indonesia' => 'success',
                            'Jepang' => 'info',
                            'China' => 'warning',
                            default => 'gray',
                        },
                    )
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('barangs_count')->label('Jumlah Barang')->counts('barangs')->badge()->color(fn(int $state): string => $state > 0 ? 'success' : 'gray')->formatStateUsing(fn(int $state): string => $state . ' item')->sortable(),

                Tables\Columns\TextColumn::make('total_stok')->label('Total Stok')->getStateUsing(fn($record) => $record->total_stok)->badge()->color('info')->formatStateUsing(fn(int $state): string => number_format($state) . ' unit')->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        \Filament\Notifications\Notification::make()
                            ->title($state ? 'Merk barang diaktifkan' : 'Merk barang dinonaktifkan')
                            ->success()
                            ->send();
                    }),

                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ])
                    ->placeholder('Semua Status'),

                Tables\Filters\SelectFilter::make('negara_asal')
                    ->label('Negara Asal')
                    ->options([
                        'Indonesia' => 'Indonesia',
                        'Jepang' => 'Jepang',
                        'Thailand' => 'Thailand',
                        'Malaysia' => 'Malaysia',
                        'China' => 'China',
                        'Korea Selatan' => 'Korea Selatan',
                        'Taiwan' => 'Taiwan',
                        'India' => 'India',
                        'Amerika Serikat' => 'Amerika Serikat',
                        'Jerman' => 'Jerman',
                        'Italia' => 'Italia',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->multiple()
                    ->placeholder('Pilih negara'),

                Tables\Filters\Filter::make('has_barangs')->label('Memiliki Barang')->query(fn(Builder $query): Builder => $query->has('barangs')),

                Tables\Filters\Filter::make('no_barangs')->label('Tidak Memiliki Barang')->query(fn(Builder $query): Builder => $query->doesntHave('barangs')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->slideOver(),
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make()->before(function (Tables\Actions\DeleteAction $action, MerkBarang $record) {
                    if ($record->barangs()->exists()) {
                        \Filament\Notifications\Notification::make()->danger()->title('Tidak dapat dihapus')->body('Merk barang ini masih memiliki barang terkait.')->persistent()->send();

                        $action->cancel();
                    }
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')->label('Aktifkan')->icon('heroicon-o-check')->color('success')->action(fn($records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion()->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')->label('Nonaktifkan')->icon('heroicon-o-x-mark')->color('danger')->action(fn($records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion()->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                        $hasBarangs = $records->filter(fn($record) => $record->barangs()->exists());

                        if ($hasBarangs->isNotEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Beberapa data tidak dapat dihapus')
                                ->body($hasBarangs->count() . ' merk barang memiliki barang terkait.')
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
                ]),
            ])
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
            'index' => Pages\ListMerkBarangs::route('/'),
            'create' => Pages\CreateMerkBarang::route('/create'),
            'edit' => Pages\EditMerkBarang::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'nama', 'negara_asal'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_active', true)->count() > 0 ? 'primary' : 'gray';
    }
}
