<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisBarangResource\Pages;
use App\Models\JenisBarang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JenisBarangResource extends Resource
{
    protected static ?string $model = JenisBarang::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Jenis Barang';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'jenis-barang';

    protected static ?string $pluralLabel = 'Jenis Barang';

    protected static ?string $modelLabel = 'Jenis Barang';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Jenis Barang')
                ->description('Kelola data jenis/kategori barang')
                ->schema([Forms\Components\TextInput::make('kode')->label('Kode Jenis')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('Kode akan digenerate otomatis'), Forms\Components\TextInput::make('nama')->label('Nama Jenis Barang')->required()->maxLength(255)->placeholder('Contoh: Spare Part, Oli, Aksesoris')->live(onBlur: true)->unique(ignoreRecord: true), Forms\Components\Textarea::make('deskripsi')->label('Deskripsi')->rows(3)->maxLength(500)->placeholder('Deskripsi singkat tentang jenis barang ini')->columnSpanFull(), Forms\Components\Toggle::make('is_active')->label('Status Aktif')->default(true)->helperText('Nonaktifkan jika jenis barang tidak digunakan lagi')->inline()])
                ->columns(2),

            Forms\Components\Section::make('Informasi Tambahan')
                ->schema([Forms\Components\Placeholder::make('created_at')->label('Dibuat pada')->content(fn(?JenisBarang $record): string => $record ? $record->created_at->format('d/m/Y H:i') : '-'), Forms\Components\Placeholder::make('updated_at')->label('Terakhir diubah')->content(fn(?JenisBarang $record): string => $record ? $record->updated_at->format('d/m/Y H:i') : '-'), Forms\Components\Placeholder::make('jumlah_barang')->label('Jumlah Barang')->content(fn(?JenisBarang $record): string => $record ? number_format($record->jumlah_barang) . ' item' : '0 item')->helperText('Total barang dengan jenis ini')])
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

                Tables\Columns\TextColumn::make('nama')->label('Nama Jenis Barang')->searchable()->sortable()->weight('medium'),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('barangs_count')->label('Jumlah Barang')->counts('barangs')->badge()->color(fn(int $state): string => $state > 0 ? 'success' : 'gray')->formatStateUsing(fn(int $state): string => $state . ' item')->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        \Filament\Notifications\Notification::make()
                            ->title($state ? 'Jenis barang diaktifkan' : 'Jenis barang dinonaktifkan')
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
                Tables\Actions\DeleteAction::make()->before(function (Tables\Actions\DeleteAction $action, JenisBarang $record) {
                    if ($record->barangs()->exists()) {
                        \Filament\Notifications\Notification::make()->danger()->title('Tidak dapat dihapus')->body('Jenis barang ini masih memiliki barang terkait.')->persistent()->send();

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
                                ->body($hasBarangs->count() . ' jenis barang memiliki barang terkait.')
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
            'index' => Pages\ListJenisBarangs::route('/'),
            'create' => Pages\CreateJenisBarang::route('/create'),
            'edit' => Pages\EditJenisBarang::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'nama', 'deskripsi'];
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
