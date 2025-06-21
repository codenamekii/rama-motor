<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SatuanBarangResource\Pages;
use App\Models\SatuanBarang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SatuanBarangResource extends Resource
{
    protected static ?string $model = SatuanBarang::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Satuan Barang';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'satuan-barang';

    protected static ?string $pluralLabel = 'Satuan Barang';

    protected static ?string $modelLabel = 'Satuan Barang';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Satuan Barang')
                ->description('Kelola data satuan barang seperti PCS, Box, Liter, dll')
                ->schema([
                    Forms\Components\TextInput::make('kode')->label('Kode Satuan')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Kode akan digenerate otomatis'),

                    Forms\Components\TextInput::make('nama')->label('Nama Satuan')->required()->maxLength(50)->placeholder('Contoh: Pieces, Box, Liter')->live(onBlur: true)->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('singkatan')
                        ->label('Singkatan')
                        ->required()
                        ->maxLength(10)
                        ->placeholder('Contoh: PCS, BOX, LTR')
                        ->unique(ignoreRecord: true)
                        ->extraAttributes(['style' => 'text-transform: uppercase'])
                        ->dehydrateStateUsing(fn($state) => strtoupper($state)),

                    Forms\Components\Textarea::make('deskripsi')->label('Deskripsi')->rows(3)->maxLength(500)->placeholder('Deskripsi singkat tentang satuan barang ini')->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true)->helperText('Nonaktifkan jika satuan tidak digunakan lagi')->inline(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Informasi Tambahan')
                ->schema([Forms\Components\Placeholder::make('created_at')->label('Dibuat pada')->content(fn(?SatuanBarang $record): string => $record ? $record->created_at->format('d/m/Y H:i') : '-'), Forms\Components\Placeholder::make('updated_at')->label('Terakhir diubah')->content(fn(?SatuanBarang $record): string => $record ? $record->updated_at->format('d/m/Y H:i') : '-'), Forms\Components\Placeholder::make('barangs_count')->label('Jumlah Barang')->content(fn(?SatuanBarang $record): string => $record ? number_format($record->barangs()->count()) . ' item' : '0 item')->helperText('Total barang dengan satuan ini')])
                ->columns(3)
                ->collapsible()
                ->collapsed()
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->searchable()->sortable()->copyable()->copyMessage('Kode berhasil disalin')->tooltip('Klik untuk menyalin'),

                Tables\Columns\TextColumn::make('nama')->label('Nama Satuan')->searchable()->sortable()->weight('medium'),

                Tables\Columns\TextColumn::make('singkatan')->label('Singkatan')->searchable()->badge()->color('info'),

                Tables\Columns\TextColumn::make('display_name')->label('Display')->getStateUsing(fn($record) => $record->display_name)->color('gray')->icon('heroicon-m-eye'),

                Tables\Columns\TextColumn::make('barangs_count')->label('Jumlah Barang')->counts('barangs')->badge()->color(fn(int $state): string => $state > 0 ? 'success' : 'gray')->formatStateUsing(fn(int $state): string => $state . ' item')->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        \Filament\Notifications\Notification::make()
                            ->title($state ? 'Satuan barang diaktifkan' : 'Satuan barang dinonaktifkan')
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

                Tables\Filters\Filter::make('has_barangs')->label('Memiliki Barang')->query(fn(Builder $query): Builder => $query->has('barangs')),

                Tables\Filters\Filter::make('no_barangs')->label('Tidak Memiliki Barang')->query(fn(Builder $query): Builder => $query->doesntHave('barangs')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make()->before(function (Tables\Actions\DeleteAction $action, SatuanBarang $record) {
                    if ($record->barangs()->exists()) {
                        \Filament\Notifications\Notification::make()->danger()->title('Tidak dapat dihapus')->body('Satuan barang ini masih memiliki barang terkait.')->persistent()->send();

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
                                ->body($hasBarangs->count() . ' satuan barang memiliki barang terkait.')
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
            'index' => Pages\ListSatuanBarangs::route('/'),
            'create' => Pages\CreateSatuanBarang::route('/create'),
            'edit' => Pages\EditSatuanBarang::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'nama', 'singkatan'];
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
