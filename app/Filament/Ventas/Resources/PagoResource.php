<?php

namespace App\Filament\Ventas\Resources;

use App\Filament\Ventas\Resources\PagoResource\Pages;
use App\Http\Controllers\UserController;
use App\Models\Banco;
use App\Models\Compra;
use App\Models\Orden;
use App\Models\Pago;
use App\Models\TipoPago;
use App\Models\User;
use App\Models\Venta;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Pago::class;

    protected static ?string $modelLabel = 'Pago';

    protected static ?string $pluralModelLabel = 'Pagos';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationIcon = 'tabler-credit-card';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $navigationGroup = 'Gestiones';

    protected static ?int $navigationSort = 2;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'delete',
            'view',
            'credit',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        MorphToSelect::make('pagable')
                            ->types([
                                /* MorphToSelect\Type::make(Orden::class)
                                    ->titleAttribute('id')
                                    ->getOptionLabelFromRecordUsing(fn (Orden $record): string => "{$record->id} - {$record->estado->value}")
                                    ->modifyOptionsQueryUsing(
                                        fn (Builder $query) => $query->whereIn('estado', ['creada', 'backorder', 'completada', 'confirmada', 'recolectada', 'preparada', 'enviada', 'finalizada'])
                                    ), */
                                MorphToSelect\Type::make(Venta::class)
                                    ->titleAttribute('id')
                                    ->getOptionLabelFromRecordUsing(fn (Venta $record): string => "{$record->id} - {$record->estado->value}")
                                    ->modifyOptionsQueryUsing(
                                        fn (Builder $query) => $query->where('estado', 'creada')
                                    ),
                                MorphToSelect\Type::make(Compra::class)
                                    ->titleAttribute('id')
                                    ->getOptionLabelFromRecordUsing(fn (Compra $record): string => "{$record->id} - {$record->estado->value}"),
                            ])
                            ->searchable()
                            ->required(),
                        Select::make('tipo_pago_id')
                            ->label('Forma de Pago')
                            ->relationship('tipoPago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO))
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('monto')
                            ->label('Monto')
                            ->prefix('Q')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $set('total', $get('monto'));
                            })
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    /* if ($get('pagable_type') == 'App\Models\Orden') {
                                        $orden = Orden::find($get('pagable_id'));
                                        if ($orden->pagos->sum('total') + $value > $orden->total) {
                                            $fail('El monto no puede ser mayor al total de la orden. Q'.$orden->total.'  Pagado: Q'.$orden->pagos->sum('total'));
                                        }
                                    } */
                                    if ($get('pagable_type') == 'App\Models\Venta') {
                                        $venta = Venta::find($get('pagable_id'));
                                        if ($venta->pagos->sum('total') + $value > $venta->total) {
                                            $fail('El monto no puede ser mayor al total de la venta. Q'.$venta->total.'  Pagado: Q'.$venta->pagos->sum('total'));
                                        }
                                    }
                                    if ($get('pagable_type') == 'App\Models\Compra') {
                                        $compra = Compra::find($get('pagable_id'));
                                        if ($compra->pagos->sum('total') + $value > $compra->total) {
                                            $fail('El monto no puede ser mayor al total de la compra. Q'.$compra->total.'  Pagado: Q'.$compra->pagos->sum('total'));
                                        }
                                    }
                                },
                            ])
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->minValue(1)
                            ->required(),
                        Hidden::make('total'),
                        Hidden::make('user_id')
                            ->default(auth()->user()->id),
                        TextInput::make('no_documento')
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if (
                                        Pago::where('banco_id', $get('banco_id'))
                                            ->where('fecha_transaccion', $get('fecha_transaccion'))
                                            ->where('no_documento', $value)
                                            ->exists()
                                    ) {
                                        $fail('La combinación de Banco, Fecha de Transacción y No. Documento ya existe en los pagos.');
                                    }
                                },
                            ])
                            ->label('No. Documento')
                            ->required(),
                        TextInput::make('no_autorizacion')
                            ->label('No. Autorización')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        TextInput::make('no_auditoria')
                            ->label('No. Auditoría')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        TextInput::make('afiliacion')
                            ->label('Afiliación')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        Select::make('cuotas')
                            ->options([1 => 1, 3 => 3, 6 => 6, 9 => 9, 12 => 12])
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                            ->required(),
                        TextInput::make('nombre_cuenta')
                            ->visible(fn (Get $get) => $get('tipo_pago_id') == 6 && $get('tipo_pago_id') != null)
                            ->required(),
                        Select::make('banco_id')
                            ->label('Banco')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'banco',
                                'banco',
                                function ($query) {
                                    return $query->whereIn('banco', Banco::BANCOS_DISPONIBLES);
                                }
                            ),
                        DatePicker::make('fecha_transaccion')
                            ->default(now())
                            ->required(),
                    ]),
                FileUpload::make('imagen')
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
                    ->columnSpanFull()
                    ->optimize('webp')
                    ->required(),
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
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pagable_type')
                    ->label('Modelo')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return class_basename($record->pagable_type).' #'.$record->pagable_id;
                    })
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cod')
                    ->label('COD')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_documento')
                    ->label('No. Documento'),
                Tables\Columns\TextColumn::make('fecha_transaccion')
                    ->label('Fecha de Transacción')
                    ->date('d/m/Y')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipoPago.tipo_pago')
                    ->label('Tipo de Pago')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('banco.banco')
                    ->label('Banco')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_acreditamiento')
                    ->label('No. Acreditamiento'),
                Tables\Columns\TextColumn::make('no_referencia')
                    ->label('No. Referencia'),
                Tables\Columns\TextColumn::make('no_autorizacion')
                    ->label('No. Autorización'),
                Tables\Columns\TextColumn::make('no_auditoria')
                    ->label('No. Auditoría'),
                Tables\Columns\TextColumn::make('afiliacion')
                    ->label('Afiliación'),
                Tables\Columns\TextColumn::make('cuotas')
                    ->label('Cuotas'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        /* if ($record->pagable_type == 'App\Models\Orden') {
                            $orden = Orden::find($record->pagable_id);
                            if ($orden->tipo_pago_id == 2) {
                                UserController::sumarSaldo(User::find($orden->cliente_id), $record->monto);
                            }
                        } */
                        if ($record->pagable_type == 'App\Models\Venta') {
                            $venta = Venta::find($record->pagable_id);
                            if ($venta->tipo_pago_id == 2) {
                                UserController::sumarSaldo(User::find($venta->cliente_id), $record->monto);
                            }
                        }
                    }),
            ])->poll('10s');
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
            'index' => Pages\ListPagos::route('/'),
        ];
    }
}
