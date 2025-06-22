<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KaryawanResource\Pages;
use App\Models\Karyawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Auth;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $navigationGroup = 'Manajemen';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'karyawan';

    protected static ?string $pluralLabel = 'Karyawan';

    protected static ?string $modelLabel = 'Karyawan';

    // Permission check - Only super_admin can access
    public static function canViewAny(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Pribadi')
                ->description('Data pribadi karyawan')
                ->schema([
                    Forms\Components\TextInput::make('nik')->label('NIK')->placeholder('Auto Generate')->disabled()->dehydrated(false)->helperText('NIK akan digenerate otomatis'),

                    Forms\Components\TextInput::make('nama')->label('Nama Lengkap')->required()->maxLength(255)->placeholder('Nama lengkap sesuai KTP'),

                    Forms\Components\Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('tempat_lahir')->label('Tempat Lahir')->maxLength(100)->placeholder('Kota kelahiran'),

                    Forms\Components\DatePicker::make('tanggal_lahir')
                        ->label('Tanggal Lahir')
                        ->maxDate(now()->subYears(17))
                        ->displayFormat('d/m/Y'),

                    Forms\Components\Select::make('status_pernikahan')
                        ->label('Status Pernikahan')
                        ->options([
                            'Belum Menikah' => 'Belum Menikah',
                            'Menikah' => 'Menikah',
                            'Duda' => 'Duda',
                            'Janda' => 'Janda',
                        ])
                        ->default('Belum Menikah'),

                    Forms\Components\TextInput::make('pendidikan_terakhir')->label('Pendidikan Terakhir')->maxLength(50)->placeholder('SD/SMP/SMA/S1/S2/S3'),

                    Forms\Components\FileUpload::make('foto')->label('Foto')->image()->imageEditor()->maxSize(1024)->directory('karyawan')->disk('public')->columnSpan(2),
                ])
                ->columns(3),

            Section::make('Kontak & Alamat')
                ->description('Informasi kontak dan alamat')
                ->schema([Forms\Components\TextInput::make('telepon')->label('No. Telepon')->tel()->required()->maxLength(20)->placeholder('08xx-xxxx-xxxx'), Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255)->placeholder('email@example.com'), Forms\Components\Textarea::make('alamat')->label('Alamat Lengkap')->required()->rows(3)->maxLength(500)->placeholder('Alamat lengkap sesuai KTP')->columnSpanFull()])
                ->columns(2),

            Section::make('Informasi Pekerjaan')
                ->description('Data pekerjaan karyawan')
                ->schema([
                    Forms\Components\TextInput::make('jabatan')->label('Jabatan')->required()->maxLength(100)->placeholder('Mekanik, Admin, Kasir, dll'),

                    // Forms\Components\TextInput::make('departemen')->label('Departemen')->maxLength(100)->placeholder('Bengkel, Administrasi, dll'),

                    Forms\Components\DatePicker::make('tanggal_masuk')->label('Tanggal Masuk')->required()->default(now())->maxDate(now())->displayFormat('d/m/Y'),

                    Forms\Components\Select::make('status_karyawan')
                        ->label('Status Karyawan')
                        ->options([
                            'Aktif' => 'Aktif',
                            'Non-Aktif' => 'Non-Aktif',
                            'Resign' => 'Resign',
                            'Pensiun' => 'Pensiun',
                        ])
                        ->default('Aktif')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $state !== 'Aktif' && !$set('tanggal_keluar') ? $set('tanggal_keluar', now()) : null),

                    Forms\Components\DatePicker::make('tanggal_keluar')->label('Tanggal Keluar')->displayFormat('d/m/Y')->visible(fn(Forms\Get $get) => $get('status_karyawan') !== 'Aktif'),

                    Forms\Components\TextInput::make('gaji_pokok')
                        ->label('Gaji Pokok')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->mask(
                            RawJs::make(
                                <<<'JS'
                                    $money($input, '.', ',', 0)
                                JS
                                ,
                            ),
                        )
                        ->stripCharacters(['.', ',']),
                ])
                ->columns(2),

            Section::make('Informasi Bank')
                ->description('Data rekening untuk penggajian')
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
                        ])
                        ->searchable(),

                    Forms\Components\TextInput::make('no_rekening')->label('No. Rekening')->maxLength(50)->placeholder('1234567890'),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Catatan')
                ->schema([Forms\Components\Textarea::make('catatan')->label('Catatan')->rows(3)->maxLength(1000)->placeholder('Catatan tambahan tentang karyawan')->columnSpanFull()])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nik')->label('NIK')->searchable()->sortable()->copyable()->weight('medium'),

                Tables\Columns\TextColumn::make('foto')
                    ->label('Foto')
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '<div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>';
                        }

                        $imageUrl = url('storage/' . $state);
                        return '<img src="' .
                            $imageUrl .
                            '"
                                    alt="Foto Karyawan"
                                    class="w-10 h-10 rounded-full object-cover border border-gray-200"
                                    onerror="this.onerror=null; this.src=\'' .
                            url('images/no-photo.png') .
                            '\'">';
                    }),

                Tables\Columns\TextColumn::make('nama')->label('Nama Karyawan')->searchable()->sortable()->weight('medium')->description(fn($record) => $record->jabatan . ($record->departemen ? ' - ' . $record->departemen : '')),

                Tables\Columns\TextColumn::make('jenis_kelamin')->label('L/P')->badge()->color(
                    fn(string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'danger',
                        default => 'gray',
                    },
                ),

                Tables\Columns\TextColumn::make('telepon')->label('Telepon')->searchable()->icon('heroicon-m-phone')->iconPosition('before')->copyable(),

                Tables\Columns\TextColumn::make('status_karyawan')->label('Status')->badge()->color(
                    fn(string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Non-Aktif' => 'warning',
                        'Resign' => 'danger',
                        'Pensiun' => 'info',
                        default => 'gray',
                    },
                ),

                Tables\Columns\TextColumn::make('masa_kerja.text')->label('Masa Kerja')->getStateUsing(fn($record) => $record->masa_kerja['text'])->badge()->color('gray'),

                Tables\Columns\TextColumn::make('gaji_pokok')->label('Gaji Pokok')->money('IDR')->sortable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tanggal_masuk')->label('Tgl Masuk')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_karyawan')
                    ->label('Status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Non-Aktif' => 'Non-Aktif',
                        'Resign' => 'Resign',
                        'Pensiun' => 'Pensiun',
                    ])
                    ->default('Aktif'),

                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),

                Tables\Filters\SelectFilter::make('jabatan')->label('Jabatan')->options(fn() => Karyawan::distinct()->pluck('jabatan', 'jabatan')->filter()->toArray())->searchable(),

                Tables\Filters\SelectFilter::make('departemen')->label('Departemen')->options(fn() => Karyawan::distinct()->pluck('departemen', 'departemen')->filter()->toArray())->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->slideOver(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resign')
                    ->label('Resign')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status_karyawan === 'Aktif')
                    ->form([Forms\Components\DatePicker::make('tanggal_keluar')->label('Tanggal Resign')->required()->default(now())->maxDate(now())])
                    ->action(function ($record, array $data) {
                        $record->resign($data['tanggal_keluar']);
                        \Filament\Notifications\Notification::make()->success()->title('Karyawan berhasil di-resign')->send();
                    }),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('nik', 'desc');
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
            'index' => Pages\ListKaryawans::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nik', 'nama', 'jabatan', 'telepon'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_karyawan', 'Aktif')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
