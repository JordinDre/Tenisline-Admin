<?php

namespace App\Filament\Ventas\Resources;

use App\Enums\EstadoVentaStatus;
use App\Filament\Ventas\Resources\VentaResource\Pages;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Models\Escala;
use App\Models\Producto;
use App\Models\TipoPago;
use App\Models\User;
use App\Models\Venta;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VentaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Venta::class;

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationIcon = 'heroicon-s-shopping-bag';

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 1;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            /* 'update', */
            'liquidate',
            /* 'factura', */
            'annular',
            'return',
            /* 'facturar',
            'credit_note', */
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('bodega_id')
                    ->relationship(
                        'bodega',
                        'bodega',
                        fn (Builder $query) => $query->whereHas('user', function ($query) {
                            $query->where('user_id', auth()->user()->id);
                        })
                    )
                    ->preload()
                    ->default(1)
                    ->columnSpanFull()
                    ->searchable()
                    ->required(),
                Wizard::make([
                    /* Wizard\Step::make('Cliente')
                        ->schema([
                            Select::make('cliente_id')
                                ->label('Cliente')
                                ->relationship('cliente', 'name', fn (Builder $query) => $query->role(['cliente', 'colaborador']))
                                ->optionsLimit(20)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('tipo_pago_id', null);
                                })
                                ->searchable(),
                            Textarea::make('observaciones')
                                ->columnSpanFull(),
                        ]), */
                    Wizard\Step::make('Productos')
                        ->schema([
                            Repeater::make('detalles')
                                ->label('')
                                ->relationship()
                                ->defaultItems(1)
                                ->minItems(1)
                                ->columns(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 6])
                                ->grid([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 3,
                                ])
                                ->schema([
                                    Select::make('producto_id')
                                        ->label('Producto')
                                        ->relationship('producto', 'descripcion')
                                        ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, 'venta', $get('../../bodega_id'), $get('../../cliente_id')))
                                        ->allowHtml()
                                        ->searchable(['id', 'codigo', 'descripcion', 'marca.marca', 'genero', 'talla'])
                                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                                            return ProductoController::searchProductos($search, 'venta', $get('../../bodega_id'), $get('../../cliente_id'));
                                        })
                                        ->optionsLimit(10)
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 6])
                                        ->required(),
                                    TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->default(1)
                                        ->minValue(1)
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->required(),
                                    TextInput::make('precio')
                                        ->label('Precio')
                                        /* ->live(onBlur: true) */
                                        /* ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state) {
                                                $userRoles = auth()->user()->roles->pluck('name');
                                                $role = collect(User::VENTA_ROLES)->first(fn ($r) => $userRoles->contains($r));
                                                $escala = Escala::where('precio', '<', $state)
                                                    ->where('producto_id', $get('producto_id'))
                                                    ->whereHas('role', fn ($q) => $q->where('name', $role))
                                                    ->orderByDesc('precio')
                                                    ->first();
                                                if ($escala) {
                                                    $set('escala_id', $escala->id);
                                                    $set('comision', $escala->comision);
                                                    $set('subtotal', round((float) $state * (float) $get('cantidad'), 2));
                                                    $set('ganancia', round((float) $state * (float) $get('cantidad') * ($escala->comision / 100), 2));

                                                    return;
                                                }
                                            }
                                        }) */
                                        ->default(0)
                                        ->readOnly()
                                        ->required()
                                        ->prefix('Q')
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        /* ->minValue(function (Get $get) {
                                            $userRoles = auth()->user()->roles->pluck('name');
                                            $role = collect(User::ORDEN_ROLES)->first(fn ($r) => $userRoles->contains($r));

                                            return Escala::where('producto_id', $get('producto_id'))
                                                ->whereHas('role', fn ($q) => $q->where('name', $role))
                                                ->orderBy('precio')
                                                ->first()->precio;
                                        }) */
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    TextInput::make('subtotal')
                                        ->label('SubTotal')
                                        ->prefix('Q')
                                        ->default(0)
                                        ->readOnly()
                                        ->columnSpan(['default' => 2,  'md' => 3, 'lg' => 4, 'xl' => 2]),
                                ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Producto'),

                        ]),
                    Wizard\Step::make('Cliente y Pagos')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 10,
                            ])
                                ->schema([
                                    /* Select::make('tipo_pago_id')
                                        ->label('Tipo de Pago')
                                        ->required()
                                        ->columnSpan(['sm' => 1, 'md' => 8])
                                        ->options(
                                            fn(Get $get) => User::find($get('cliente_id'))?->tipo_pagos->pluck('tipo_pago', 'id') ?? []
                                        )
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            $set('pagos', []);
                                        })
                                        ->searchable()
                                        ->rules([
                                            fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                if ($get('total') < collect($get('pagos'))->sum('monto') && $value == 4) {
                                                    $fail('El monto total de los pagos no puede ser mayor al total de la orden.');
                                                }
                                            },
                                        ])
                                        ->preload(), */
                                    Select::make('cliente_id')
                                        ->label('Cliente')
                                        ->relationship('cliente', 'name', fn (Builder $query) => $query->role(['cliente', 'colaborador']))
                                        ->optionsLimit(20)
                                        ->required()
                                        ->columnSpan(['sm' => 1, 'md' => 9])
                                        ->searchable(),
                                    /* Toggle::make('facturar_cf')
                                        ->inline(false)
                                        ->live()
                                        ->disabled(fn(Get $get) => $get('total') >= Factura::CF)
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            if (! $get('facturar_cf')) {
                                                $set('comp', false);
                                            }
                                        })
                                        ->label('Facturar CF'),
                                       Toggle::make('comp')
                                        ->inline(false)
                                        ->label('Comp')
                                        ->disabled(fn (Get $get) => $get('facturar_cf') == false || $get('total') >= Factura::CF), */
                                ]),
                            Repeater::make('pagos')
                                ->label('Pagos')
                                ->required()
                                ->relationship()
                                ->minItems(1)
                                ->defaultItems(1)
                                ->columns(7)
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Forma de Pago')
                                        ->relationship('tipoPago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO_VENTA))
                                        ->required()
                                        ->live()
                                        ->columnSpan(['sm' => 1, 'md' => 1])
                                        ->searchable()
                                        ->preload(),
                                    TextInput::make('monto')
                                        ->label('Monto')
                                        ->prefix('Q')
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->minValue(1)
                                        ->required(),
                                    TextInput::make('no_documento')
                                        ->label('No. Documento o Autorización'),
                                    /* TextInput::make('no_autorizacion')
                                        ->label('No. Autorización')
                                        ->visible(fn(Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                        ->required(),
                                    TextInput::make('no_auditoria')
                                        ->label('No. Auditoría')
                                        ->visible(fn(Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                        ->required(),
                                    TextInput::make('afiliacion')
                                        ->label('Afiliación')
                                        ->visible(fn(Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                        ->required(),
                                    Select::make('cuotas')
                                        ->options([1 => 1, 3 => 3, 6 => 6, 9 => 9, 12 => 12])
                                        ->visible(fn(Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null)
                                        ->required(),
                                    TextInput::make('nombre_cuenta')
                                        ->visible(fn(Get $get) => $get('tipo_pago_id') == 6 && $get('tipo_pago_id') != null)
                                        ->required(),
                                    Select::make('banco_id')
                                        ->label('Banco')
                                        ->columnSpan(['sm' => 1, 'md' => 2])
                                        ->required()
                                        ->relationship('banco', 'banco')
                                        ->searchable()
                                        ->preload(), */
                                    DatePicker::make('fecha_transaccion')
                                        ->default(now())
                                        ->required(),
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
                                ])
                                ->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Pago'),
                            Textarea::make('observaciones')
                                ->columnSpanFull(),
                        ]),
                ])->skippable()->columnSpanFull(),
                Grid::make(['default' => 2])
                    ->schema([
                        TextInput::make('subtotal')
                            ->prefix('Q')
                            ->readOnly()
                            ->label('SubTotal'),
                        TextInput::make('total')
                            ->readOnly()
                            ->prefix('Q')
                            /* ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $user = User::find($get('cliente_id'));
                                    if ($get('tipo_pago_id') == 2 && $value > ($user->credito - $user->saldo)) {
                                        $fail('El Cliente no cuenta con suficiente crédito para realizar la compra.');
                                    }
                                    if ($user && in_array($user->nit, [null, '', 'CF', 'cf', 'cF', 'Cf'], true) && $value >= \App\Models\Factura::CF) {
                                        $fail('El Cliente no cuenta con NIT registrado para el valor de la Orden.');
                                    }
                                },
                            ]) */
                            ->label('Total'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->numeric()
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.name')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->searchable()
                    ->label('Vendedor')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')->badge(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('detalles.producto.id')
                    ->label('ID Producto')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('detalles.producto.codigo')
                    ->label('Cod Producto')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('detalles.producto.descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('detalles.producto.marca.marca')
                    ->label('Marca')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pagos.tipoPago.tipo_pago')
                    ->label('Forma de Pago')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('factura.fel_uuid')
                    ->label('Fel No. Autorización')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('factura.fel_numero')
                    ->label('Fel No. DTE')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('factura.fel_serie')
                    ->label('Fel No. Serie')
                    ->sortable()
                    ->copyable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('factura.fel_fecha')
                    ->label('Fel Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('anulacion.fel_uuid')
                    ->label('Anulación Autorización')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('anulacion.fel_numero')
                    ->label('Anulación No. DTE')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('anulacion.fel_serie')
                    ->label('Anulación No. Serie')
                    ->sortable()
                    ->copyable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('anulacion.fel_fecha')
                    ->label('Anulación Fel Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('anulacion.motivo')
                    ->label('Motivo Anulación')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('devolucion.fel_uuid')
                    ->label('Devolución Autorización')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('devolucion.fel_numero')
                    ->label('Devolución No. DTE')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('devolucion.fel_serie')
                    ->label('Devolución No. Serie')
                    ->sortable()
                    ->copyable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('devolucion.fel_fecha')
                    ->label('Devolución Fel Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('devolucion.motivo')
                    ->label('Motivo Devolución')
                    ->sortable()
                    ->copyable()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                /* Tables\Columns\TextColumn::make('detalles.producto.presentacion.presentacion')
                    ->label('Presentación')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true), */
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                /* SelectFilter::make('tipo_pago_id')
                    ->label('Tipo de Pago')
                    ->multiple()
                    ->options(
                        TipoPago::CLIENTE_PAGOS_ARRAY
                    ), */
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->multiple()
                    ->options(EstadoVentaStatus::class),
            ], layout: FiltersLayout::AboveContent)->persistFiltersInSession()
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Action::make('venta')
                        ->icon('heroicon-o-document-arrow-down')
                        ->modalContent(fn (Venta $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Venta #'.$record->id,
                                'route' => route('pdf.venta', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    Action::make('factura')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn ($record) => auth()->user()->can('factura', $record))
                        ->modalContent(fn (Venta $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Factura Venta #'.$record->id,
                                'route' => route('pdf.factura.venta', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    Action::make('nota_credito')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn ($record) => auth()->user()->can('credit_note', $record))
                        ->modalContent(fn (Venta $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Factura Venta #'.$record->id,
                                'route' => route('pdf.nota-credito.venta', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    /* Action::make('facturar')
                        ->label('Facturar')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-document-text')
                        ->color('indigo')
                        ->action(fn (Venta $record) => VentaController::facturar($record))
                        ->visible(fn ($record) => auth()->user()->can('facturar', $record)), */
                    Action::make('liquidate')
                        ->label('Liquidar')
                        ->color('success')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-clipboard-document-check')
                        ->action(fn (Venta $record) => VentaController::liquidar($record))
                        ->visible(fn ($record) => auth()->user()->can('liquidate', $record)),
                    Action::make('annular')
                        ->label('Anular')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(fn (array $data, Venta $record) => VentaController::anular($data, $record))
                        ->form([
                            Textarea::make('motivo')
                                ->label('Motivo de Anulación')
                                ->minLength(10)
                                ->required(),
                        ])
                        ->visible(fn ($record) => auth()->user()->can('annular', $record)),
                    Action::make('return')
                        ->label('Devolver')
                        ->color('orange')
                        ->slideOver()
                        ->closeModalByClickingAway(false)
                        ->modalWidth(MaxWidth::SixExtraLarge)
                        ->icon('tabler-truck-return')
                        ->visible(fn ($record) => auth()->user()->can('return', $record))
                        ->fillForm(fn (Venta $record): array => [
                            'motivo' => $record->motivo,
                            'detalles' => $record->detalles->map(function ($detalle) {
                                return [
                                    'id' => $detalle->id,
                                    'producto_id' => $detalle->producto_id,
                                    'cantidad' => $detalle->cantidad,
                                    'devuelto' => $detalle->devuelto,
                                    'codigo_nuevo' => '',
                                ];
                            })->toArray(),
                        ])
                        ->form([
                            Grid::make(['default' => 1, 'md' => 6])
                                ->schema([
                                    Textarea::make('motivo')
                                        ->required()
                                        ->label('Motivo de Devolución')
                                        ->minLength(10)
                                        ->columnSpan(['default' => 1, 'md' => 4]),
                                    // TextInput::make('costo_envio')
                                    //     ->label('Costo Envío')
                                    //     ->columnSpan(1)
                                    //     ->disabled(),
                                    // TextInput::make('apoyo')
                                    //     ->label('Apoyo')
                                    //     ->inputMode('decimal')
                                    //     ->rule('numeric')
                                    //     ->columnSpan(1)
                                    //     ->minValue(0)
                                    //     ->default(0)
                                    //     ->required(),
                                ]),
                            Repeater::make('detalles')
                                ->label('')
                                ->collapsible()
                                ->addable(false)
                                ->deletable(false)
                                ->schema([
                                    Select::make('producto_id')
                                        ->label('Producto')
                                        ->options(function () {
                                            return \App\Models\Producto::with('marca')
                                                ->get()
                                                ->mapWithKeys(fn ($producto) => [
                                                    $producto->id => ProductoController::renderProductos($producto, 'venta', 1),
                                                ])
                                                ->toArray();
                                        })
                                        ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, 'venta', 1))
                                        ->allowHtml()
                                        ->searchable(['id'])
                                        ->getSearchResultsUsing(function (string $search): array {
                                            return ProductoController::searchProductos($search, 'venta', 1);
                                        })
                                        ->optionsLimit(12)
                                        ->required(),
                                    Grid::make(4)
                                        ->schema([
                                            TextInput::make('cantidad')
                                                ->label('Cantidad')
                                                ->disabled(),
                                            TextInput::make('devuelto')
                                                ->label('Devuelto')
                                                ->inputMode('decimal')
                                                ->rule('numeric')
                                                ->minValue(0)
                                                ->default(0)
                                                ->required(),
                                            TextInput::make('codigo_nuevo')
                                                ->label('Nuevo Código (Marchamo)')
                                                ->required()
                                                ->dehydrated(true)
                                                ->columnSpan(2),
                                            // TextInput::make('devuelto_mal')
                                            //     ->label('De lo Devuelto, Cuánto está Mal?')
                                            //     ->inputMode('decimal')
                                            //     ->rule('numeric')
                                            //     ->minValue(0)
                                            //     ->default(0)
                                            //     ->required(),
                                        ]),
                                ]),
                        ])
                        ->action(function (array $data, Venta $record): void {
                            VentaController::devolver($data, $record);
                        }),
                ])
                    ->link()
                    ->label('Acciones'),
            ], position: ActionsPosition::BeforeColumns)
            ->poll('60s')
            ->bulkActions([
                BulkAction::make('liquidar')
                    ->label('Liquidar')
                    ->color('success')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->closeModalByClickingAway(false)
                    ->fillForm([
                        'no_documento' => '',
                    ])
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('tipo_pago_id')
                                    ->label('Tipo de Pago')

                                    ->preload()
                                    ->placeholder('Seleccione')
                                    ->live()
                                    ->searchable(),
                                //     TextInput::make('monto')
                                //         ->label('Monto')
                                //         ->prefix('Q')
                                //         ->live(onBlur: true)
                                //         ->inputMode('decimal')
                                //         ->rule('numeric')
                                //         ->minValue(1)
                                //         ->required(),
                                TextInput::make('no_documento')
                                    ->label('No. Documento o Autorización'),
                                // DatePicker::make('fecha_transaccion')
                                //     ->default(now())
                                //     ->required(),
                            ]),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        VentaController::liquidarBulk($records, $data);
                    }),
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
            'index' => Pages\ListVentas::route('/'),
            'create' => Pages\CreateVenta::route('/create'),
            'view' => Pages\ViewVenta::route('/{record}'),
            'edit' => Pages\EditVenta::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }
}
