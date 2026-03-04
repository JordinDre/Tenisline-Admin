<?php

namespace App\Filament\Ventas\Resources;

use App\Models\User;
use Filament\Tables;
use App\Models\Venta;
use App\Models\Escala;
use App\Models\Direccion;
use Filament\Forms\Get;
use App\Models\Producto;
use App\Models\TipoPago;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\GuatexService;
use App\Enums\EstadoVentaStatus;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use App\Http\Controllers\VentaController;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\GUATEXController;
use Filament\Tables\Enums\ActionsPosition;
use App\Http\Controllers\ProductoController;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use App\Filament\Ventas\Resources\VentaResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class VentaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Venta::class;

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

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
                            $query->where('user_id', Auth::user()->id);
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
                                                $userRoles = Auth::user()->roles->pluck('name');
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
                                            $userRoles = Auth::user()->roles->pluck('name');
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
                                        ->relationship('tipo_pago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO_VENTA))
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
                                        ->label('No. Documento o Autorización')
                                        ->required(fn (Get $get) => ! in_array(optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago, ['CONTADO', 'PAGO CONTRA ENTREGA'])),
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
                                        ->required(), */
                                    Select::make('banco_id')
                                        ->label('Banco')
                                        ->columnSpan(['sm' => 1, 'md' => 2])
                                        ->required(fn (Get $get) => ! in_array(optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago, ['CONTADO', 'PAGO CONTRA ENTREGA']))
                                        ->relationship('banco', 'banco', function ($query) {
                                            return $query->whereIn('banco', Banco::BANCOS_DISPONIBLES);
                                        })
                                        ->searchable()
                                        ->preload(),
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
            ->paginated([10, 25, 50])
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
                Tables\Columns\TextColumn::make('tracking')
                    ->label('Tracking')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('paquetes')
                    ->label('Paquetes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                Tables\Columns\TextColumn::make('tipo_envio')
                    ->label('Tipo de Envío')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('codigo_destino_guatex')
                    ->label('Código Destino GUATEX')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('municipio_destino_guatex')
                    ->label('Municipio Destino GUATEX')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('punto_destino_guatex')
                    ->label('Punto Destino GUATEX')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('factura.fel_uuid')
                    ->label('Fel No. Autorización')
                    ->sortable()
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && ! $record->debeOcultarFactura()),
                Tables\Columns\TextColumn::make('factura.fel_numero')
                    ->label('Fel No. DTE')
                    ->sortable()
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && ! $record->debeOcultarFactura()),
                Tables\Columns\TextColumn::make('factura.fel_serie')
                    ->label('Fel No. Serie')
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && ! $record->debeOcultarFactura()),
                Tables\Columns\TextColumn::make('factura.fel_fecha')
                    ->label('Fel Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && ! $record->debeOcultarFactura()),

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
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    Action::make('validarPago')
                        ->label('Validar Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function ($record) {
                            $user = \Filament\Facades\Filament::auth()->user();

                            return $record->estado === EstadoVentaStatus::ValidacionPago
                                && $user
                                && $user->hasAnyRole(['admin', 'administrador', 'super_admin']);
                        })
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->estado = 'creada';
                            $record->save();

                            VentaController::facturar($record);

                            Notification::make()
                                ->title('Pago validado')
                                ->body('El pago ha sido confirmado y se generó la factura.')
                                ->success()
                                ->send();
                        }),
                    Action::make('anularPago')
                        ->label('Anular Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('danger')
                        ->visible(function ($record) {
                            $user = \Filament\Facades\Filament::auth()->user();

                            return $record->estado === EstadoVentaStatus::ValidacionPago
                                && $user
                                && $user->hasAnyRole(['admin', 'administrador', 'super_admin']);
                        })
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->estado = 'anulada';
                            $record->save();

                            Notification::make()
                                ->title('Pago anulado')
                                ->body('El pago ha sido anulado, la venta ha sido colocada en anuladas.')
                                ->success()
                                ->send();
                        }),
                    Action::make('factura')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn ($record) => $record && Auth::user()->can('factura', $record) && ! $record->debeOcultarFactura())
                        ->disabled(fn ($record) => $record && $record->debeOcultarFactura())
                        ->modalContent(fn (Venta $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Factura Venta #'.$record->id,
                                'route' => route('pdf.factura.venta', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    Action::make('nota_credito')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn ($record) => Auth::user()->can('credit_note', $record))
                        ->modalContent(fn (Venta $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Factura Venta #'.$record->id,
                                'route' => route('pdf.nota-credito.venta', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    /* Action::make('facturar')
                        ->label('Facturar')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-document-text')
                        ->color('indigo')
                        ->action(fn (Venta $record) => VentaController::facturar($record))
                        ->visible(fn ($record) => Auth::user()->can('facturar', $record)), */
                    Action::make('enviar')
                        ->label('Agregar Destino GUATEX')
                        ->color('success')
                        ->icon('heroicon-o-truck')
                        ->requiresConfirmation()
                        ->modalSubmitAction(fn ($action) => $action->extraAttributes(['wire:loading.attr' => 'disabled']))
                        ->modalCancelAction(fn ($action) => $action->extraAttributes(['wire:loading.attr' => 'disabled']))
                        ->form(function (Venta $record) {
                            $direcciones = $record->cliente->direcciones;

                            if ($direcciones->isEmpty()) {
                                return [
                                    Placeholder::make('error_direccion')
                                        ->label('')
                                        ->content('El cliente no tiene direccion agregada'),
                                ];
                            }

                            return [
                                Select::make('direccion_id')
                                    ->label('Seleccionar Dirección')
                                    ->options($direcciones->mapWithKeys(function ($d) {
                                        return [$d->id => "{$d->direccion} - {$d->referencia} - {$d->municipio->municipio} - {$d->departamento->departamento}"];
                                    }))
                                    ->required()
                                    ->live()
                                    ->searchable(),

                                Select::make('destino_guatex')
                                    ->label('Destino GUATEX')
                                    ->options(fn (Get $get) => static::opcionesGuatex($get('direccion_id')))
                                    ->required()
                                    ->searchable()
                                    ->loadingMessage('Cargando destinos...')
                                    ->disabled(fn (Get $get) => ! $get('direccion_id')),

                                TextInput::make('paquetes')
                                    ->numeric()
                                    ->default(1),
                            ];
                        })
                            ->action(function (array $data, Venta $record) {

                                DB::transaction(function () use ($data, $record) {

                                    $destino = json_decode($data['destino_guatex'], true);

                                    $record->update([
                                        'codigo_destino_guatex'    => $destino['CODIGO'],
                                        'municipio_destino_guatex' => $destino['MUNICIPIO'],
                                        'punto_destino_guatex'     => $destino['PUNTO_COBERTURA'],
                                        'paquetes'          => $data['paquetes'],
                                    ]);

                                    $service = app(\App\Services\GuatexService::class);
                                    $guia = $service->generarYGuardarGuia(
                                        venta: $record,
                                        paquetes: $data['paquetes'],
                                        direccionId: $data['direccion_id'],
                                        tipo: 'paquetes'
                                    );

                                    $record->update([
                                        'tracking' => $guia->tracking,
                                        'estado'   => \App\Enums\EstadoVentaStatus::Enviado,
                                    ]);

                                });

                                    Notification::make()
                                        ->title('Guía GUATEX generada correctamente')
                                        ->success()
                                        ->send();
                            })
                            ->visible(fn ($record) => Auth::user()->can('liquidate', $record) && $record->tipo_envio == "guatex" && $record->estado == \App\Enums\EstadoVentaStatus::Creada),
                    Action::make('ver_guia_guatex')
                        ->label('Ver guía GUATEX')
                        ->icon('heroicon-o-document-arrow-down')
                        ->modalContent(fn (Venta $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title'  => 'Guía GUATEX #'.$record->tracking,
                                'route'  => route('guatex.generar_guias_pdf', ['id' => $record->id]),
                                'open'   => true,
                            ]
                        ))
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false)
                        ->visible(fn (Venta $record) =>
                            $record->tracking !== null
                        ),
                    Action::make('eliminar_guia_guatex')
                        ->label('Eliminar guía GUATEX')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar guía GUATEX')
                        ->modalDescription('Esta acción eliminará la guía en GUATEX y no se puede deshacer.')
                        ->action(function (Venta $record) {

                            if (! $record->tracking) {
                                Notification::make()
                                    ->title('La venta no tiene guía asignada')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            DB::transaction(function () use ($record) {

                                $service = app(\App\Services\GuatexService::class);

                                $service->eliminarGuia($record->tracking);

                                $record->guias()->delete();

                                $record->update([
                                    'tracking' => null,
                                    'estado'   => \App\Enums\EstadoVentaStatus::Creada,
                                ]);
                            });

                            Notification::make()
                                ->title('Guía GUATEX eliminada correctamente')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Venta $record) =>
                            $record->tracking !== null &&
                            $record->estado === \App\Enums\EstadoVentaStatus::Enviado
                        ),
                    Action::make('tracking')
                        ->label('Agregar Tracking')
                        ->color('warning')
                        ->icon('heroicon-o-map-pin')
                        ->visible(fn ($record) => $record->estado === EstadoVentaStatus::Enviado)
                        ->form([
                            TextInput::make('tracking')
                                ->label('Código de Tracking')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('paquetes')
                                ->label('Cantidad de Paquetes')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1),
                        ])
                        ->fillForm(fn (Venta $record): array => [
                            'tracking' => $record->tracking,
                            'paquetes' => $record->paquetes ?? 1,
                        ])
                        ->action(function (array $data, Venta $record): void {
                            $record->tracking = $data['tracking'];
                            $record->paquetes = $data['paquetes'];
                            $record->save();

                            Notification::make()
                                ->title('Tracking agregado')
                                ->body('El código de tracking y paquetes han sido guardados correctamente.')
                                ->success()
                                ->send();
                        }),
                    Action::make('liquidate')
                        ->label('Liquidar')
                        ->color('success')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-clipboard-document-check')
                        ->action(fn (Venta $record) => VentaController::liquidar($record))
                        ->visible(fn ($record) => Auth::user()->can('liquidate', $record)),
                    Action::make('finalizar')
                        ->label('Finalizar')
                        ->color('info')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-paper-airplane')
                        ->action(fn (Venta $record) => VentaController::liquidar($record))
                        ->visible(fn ($record) => Auth::user()->can('finalziar', $record)),
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
                        ->visible(fn ($record) => Auth::user()->can('annular', $record)),
                    Action::make('return')
                        ->label('Devolver')
                        ->color('orange')
                        ->slideOver()
                        ->closeModalByClickingAway(false)
                        ->modalWidth(MaxWidth::SixExtraLarge)
                        ->icon('tabler-truck-return')
                        ->visible(fn ($record) => Auth::user()->can('return', $record))
                        ->fillForm(fn (Venta $record): array => [
                            'motivo' => $record->motivo,
                            'detalles' => $record->detalles->map(function ($detalle) {
                                return [
                                    'id' => $detalle->id,
                                    'producto_id' => $detalle->producto_id,
                                    'cantidad' => $detalle->cantidad,
                                    'devuelto' => $detalle->devuelto,
                                    'codigo_nuevo' => $detalle->producto->codigo ?? '',
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
                                        ->options(function (Get $get, $record) {
                                            // Solo cargar los productos que están en esta venta específica
                                            if (! $record) {
                                                return [];
                                            }

                                            return ProductoController::getProductosDeVenta($record->id);
                                        })
                                        ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, 'venta', 1))
                                        ->allowHtml()
                                        ->disabled() // Deshabilitado porque ya está pre-seleccionado
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
                                    ->relationship('tipo_pago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO_VENTA))
                                    ->required()
                                    ->preload()
                                    ->placeholder('Seleccione')
                                    ->live(),
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
        $query = parent::getEloquentQuery()
            ->with([
                'detalles.producto',
                'cliente',
                'asesor',
                'bodega',
                'pagos.tipoPago',
                'factura',
            ])
            ->orderByDesc('created_at');

        $user = \Filament\Facades\Filament::auth()->user();

        if ($user->hasAnyRole(['administrador', 'super_admin'])) {
            return $query;
        }

        if ($user && $user->bodegas()->exists()) {
            $bodegaIds = $user->bodegas->pluck('id')->toArray();

            return $query->whereIn('bodega_id', $bodegaIds);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canCreate(): bool
    {
        return ! static::hayBloqueo();
    }

    public static function hayBloqueo(): bool
    {
        return Venta::where('estado', 'validacion_pago')
            ->where('created_at', '<=', now()->subHour())
            ->exists();
    }

    protected static function opcionesGuatex($direccionId): array
    {
        if (! $direccionId) {
            return [];
        }

        $direccion = Direccion::with(['departamento', 'municipio'])
            ->find($direccionId);

        if (
            ! $direccion ||
            ! $direccion->departamento ||
            ! $direccion->municipio
        ) {
            return [];
        }

        $destinos = app(\App\Http\Controllers\GUATEXController::class)
            ->obtenerDestinos(
                $direccion->departamento->departamento
            );

        if (! is_array($destinos) || empty($destinos)) {
            return [];
        }

        return collect($destinos)->mapWithKeys(function ($destino) {
            $frecuencia = $destino['FRECUENCIA_VISITA'] ?? 'No especificada';

            return [
                json_encode($destino) =>
                    "{$destino['CODIGO']} - {$destino['NOMBRE']} - {$destino['MUNICIPIO']} - {$destino['DEPARTAMENTO']} - {$frecuencia}",
            ];
        })->toArray();
    }
}
