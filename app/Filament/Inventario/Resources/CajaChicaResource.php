<?php

namespace App\Filament\Inventario\Resources;

use App\Filament\Inventario\Resources\CajaChicaResource\Pages;
use App\Http\Controllers\CajaChicaController;
use App\Models\CajaChica;
use App\Models\Pago;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CajaChicaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = CajaChica::class;

    protected static ?string $navigationIcon = 'tabler-box';

    protected static ?string $pluralModelLabel = 'Caja Chica';

    protected static ?string $navigationLabel = 'Caja Chica';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 3;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'confirm',
            'annular',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Select::make('bodega_id')
                            ->relationship(
                                'bodega',
                                'bodega',
                                fn (Builder $query) => $query
                                    ->whereHas('user', fn ($q) => $q->where('user_id', auth()->id())
                                    )
                                    ->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                                    ->where('bodega', 'not like', '%bodega%')
                            )
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('detalles', []);
                            })
                            ->searchable()
                            ->required(),
                        Hidden::make('user_id')
                            ->default(auth()->user()->id),
                        Select::make('proveedor_id')
                            ->required()
                            ->searchable()
                            ->visible(auth()->user()->can('view_supplier_producto'))
                            ->relationship('proveedor', 'name', fn (Builder $query) => $query->role('proveedor')),
                        Forms\Components\TextInput::make('detalle_gasto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('autoriza')
                            ->label('Quien autoriza')
                            ->required()
                            ->maxLength(255),

                    ]),
                Forms\Components\Repeater::make('pagos')
                    ->relationship()
                    ->label('Información de Pago')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('no_documento')
                                    ->label('No. Documento')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('monto')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required(),
                                Hidden::make('user_id')
                                    ->default(auth()->user()->id),
                                FileUpload::make('imagen')
                                    ->required()
                                    ->image()
                                    ->downloadable()
                                    ->label('Imágen')
                                    ->imageEditor()
                                    ->disk(config('filesystems.disks.s3.driver'))
                                    ->directory(config('filesystems.default'))
                                    ->visibility('public')
                                    ->appendFiles()
                                    ->maxSize(5000)
                                    ->resize(50)
                                    ->openable()
                                    ->columnSpan(['sm' => 1, 'md' => 3])
                                    ->optimize('webp'),
                            ]),
                    ])
                    ->defaultItems(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Vendedor')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('proveedor.name')
                    ->label('Proveedor')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pagos.monto')
                    ->label('Monto')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pagos.no_documento')
                    ->label('No. Documento'),
                Tables\Columns\TextColumn::make('detalle_gasto')
                    ->label('Detalle del gasto'),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->label('Bodega'),
                Tables\Columns\TextColumn::make('autoriza')
                    ->label('Quien Autorizo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye'),
                Action::make('confirm')
                    ->label('Confirmar')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn (CajaChica $record) => CajaChicaController::confirmar($record))
                    ->visible(fn ($record) => auth()->user()->can('confirm', $record)),
                Action::make('annular')
                    ->label('Anular')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(fn (CajaChica $record) => CajaChicaController::anular($record))
                    ->visible(fn ($record) => auth()->user()->can('annular', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCajaChicas::route('/'),
            'create' => Pages\CreateCajaChica::route('/create'),
            'edit' => Pages\EditCajaChica::route('/{record}/edit'),
        ];
    }

    public static function create(array $data): ?CajaChica
    {
        return DB::transaction(function () use ($data) {
            $cajaChicaData = collect($data)
                ->only(['detalle_gasto', 'imagen'])
                ->toArray();

            $cajaChica = CajaChica::create($cajaChicaData);

            if ($cajaChica) {
                $pagoData = collect($data)
                    ->only(['no_documento', 'autoriza', 'monto'])
                    ->merge([
                        'pagable_id' => $cajaChica->id,
                        'pagable_type' => CajaChica::class,
                    ])
                    ->toArray();

                Pago::create($pagoData);

                return $cajaChica;
            }

            return null;
        });
    }
}
