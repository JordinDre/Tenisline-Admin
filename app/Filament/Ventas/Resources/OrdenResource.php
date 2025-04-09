<?php

namespace App\Filament\Ventas\Resources;

use App\Enums\EnvioStatus;
use App\Enums\EstadoOrdenStatus;
use App\Filament\Ventas\Resources\OrdenResource\Pages;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\ProductoController;
use App\Models\Inventario;
use App\Models\Orden;
use App\Models\Producto;
use App\Models\TipoPago;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class OrdenResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Orden::class;

    protected static ?string $modelLabel = 'Orden';

    protected static ?string $pluralModelLabel = 'Ordenes';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationIcon = 'tabler-square-chevrons-down';

    protected static ?string $navigationLabel = 'Ordenes';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 3;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            /* 'create', */
            'update',
            'products',
            'confirm',
            'collect',
            'terminate',
            'prepare',
            'goback',
            'receipt',
            'validate_pay',
            'finish',
            'send',
            'cancelguide',
            'print_guides',
            'liquidate',
            'factura',
            'annular',
            'return',
            'facturar',
            'credit_note',
            'assign',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i:s')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('observaciones')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_enviada')
                    ->label('Atraso')
                    ->formatStateUsing(function ($record) {
                        if ($record->fecha_enviada && ! $record->fecha_finalizada) {
                            return (int) Carbon::parse($record->fecha_enviada)->diffInDays(now()).' dia(s)';
                        }

                        return 'No aplica';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('prefechado')
                    ->sortable()
                    ->dateTime('d/m/Y'),
                Tables\Columns\TextColumn::make('estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('cliente.nit')
                    ->label('NIT')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.name')
                    ->label('Nombre Comercial')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.razon_social')
                    ->label('Razón Social')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_envio')
                    ->label('Tipo de Envío'),
                Tables\Columns\TextColumn::make('envio')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('GTQ')
                    ->copyable()
                    ->sortable(),
                ToggleColumn::make('pago_validado')
                    ->label('Pago Validado')
                    ->visible(auth()->user()->can('validate_pay_orden')),
                Tables\Columns\TextColumn::make('pagos')
                    ->label('Pagado')
                    ->formatStateUsing(function ($record) {
                        return 'Q '.$record->pagos->sum('monto');
                    })
                    ->copyable(),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->searchable()
                    ->label('Bodega'),
                Tables\Columns\TextColumn::make('bodega')
                    ->label('Capital')
                    ->visible(auth()->user()->can('validate_pay_orden'))
                    ->formatStateUsing(function ($record) {
                        $isComplete = $record->detalles->every(function ($detalle) {
                            $inventario = Inventario::where('producto_id', $detalle->producto_id)
                                ->where('bodega_id', 2)
                                ->first();
                            $existenciaInicial = $inventario ? $inventario->existencia : 0;
                            $cantidadTotal = $detalle->cantidad;

                            return $existenciaInicial >= $cantidadTotal;
                        });

                        return $isComplete ? 'Completo' : 'Incompleto';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('tiene_pagos')
                    ->label('Pagos')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        return $record->pagos()->exists();
                    }),
                Tables\Columns\TextColumn::make('tipo_pago.tipo_pago')
                    ->label('Tipo de Pago')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('enlinea')
                    ->label('Enlinea')
                    ->boolean(),
                Tables\Columns\TextColumn::make('guias.tipo')
                    ->label('Tipo de Guia')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('guias.tracking')
                    ->label('Guias')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
                Tables\Columns\TextColumn::make('guias.cantidad')
                    ->label('Cantidad de Guias')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable(),
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
                Tables\Columns\TextColumn::make('detalles.producto.presentacion.presentacion')
                    ->label('Presentación')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('recibio')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado_envio')
                    ->copyable()
                    ->sortable(),
                /* Tables\Columns\TextColumn::make('factura.numero')
                    ->sortable(), */
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo_pago_id')
                    ->label('Tipo de Pago')
                    ->multiple()
                    ->options(
                        TipoPago::CLIENTE_PAGOS_ARRAY
                    ),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->multiple()
                    ->options(EstadoOrdenStatus::class),
                SelectFilter::make('tipo_envio')
                    ->label('Tipo de Envío')
                    ->multiple()
                    ->options(EnvioStatus::class),

            ], layout: FiltersLayout::AboveContent)->persistFiltersInSession()
            ->actions([
                Action::make('collect')
                    ->label('Recolectar')
                    ->color('success')
                    ->form([
                        Select::make('recolector_id')
                            ->label('Recolector')
                            ->relationship('recolector', 'name', function (Builder $query, Orden $record) {
                                $query->role('recolector')
                                    ->whereHas('bodegas', function ($query) use ($record) {
                                        $query->where('bodegas.id', $record->bodega_id);
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->icon('heroicon-c-play')
                    ->action(fn (array $data, Orden $record) => OrdenController::recolectar($record, $data))
                    ->visible(fn ($record) => auth()->user()->can('collect', $record)),
                /* Action::make('guias')
                    ->label('Imprimir Guias')
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->modalContent(fn(?Orden $record): View => view(
                        'filament.pages.actions.iframe',
                        [
                            'record' => $record,
                            'title' => $record ? 'Guias de Orden #' . $record->id : 'Sin Orden',
                            'route' => $record ? route('pdf.guias', ['id' => $record->id]) : '#',
                            'open' => false,
                        ]
                    ))
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->slideOver()
                    ->stickyModalHeader()
                    ->modalSubmitAction(false)
                    ->closeModalByClickingAway(false)
                    ->visible(fn($record) => auth()->user()->can('print_guides', $record)), */
                Action::make('guias')
                    ->label('Imprimir Guias')
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Orden $record) => route('pdf.guias', ['id' => $record->id]), shouldOpenInNewTab: true)
                    /*  ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->slideOver()
                    ->stickyModalHeader()
                    ->modalSubmitAction(false)
                    ->closeModalByClickingAway(false) */
                    ->visible(fn ($record) => auth()->user()->can('print_guides', $record)),
                Action::make('prepare')
                    ->color('success')
                    ->closeModalByClickingAway(false)
                    ->modalWidth(MaxWidth::Small)
                    ->label('Preparar')
                    ->icon('tabler-package-export')
                    ->action(fn (Orden $record) => OrdenController::preparar($record))
                    ->visible(fn ($record) => auth()->user()->can('prepare', $record)),
                Action::make('terminate')
                    ->label('Terminar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('cantidad')
                            ->label('Cantidad de Paquetes')
                            ->inputMode('decimal')
                            ->default(0)
                            ->minValue(0)
                            ->rule('numeric')
                            ->required(),
                    ])
                    ->icon('heroicon-m-stop')
                    ->action(fn (array $data, Orden $record) => OrdenController::terminar($data, $record))
                    ->visible(fn ($record) => auth()->user()->can('terminate', $record)),
                Action::make('facturar')
                    ->label('Facturar')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-document-text')
                    ->color('indigo')
                    ->action(fn ($record) => OrdenController::facturar($record))
                    ->visible(fn ($record) => auth()->user()->can('facturar', $record)),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()->visible(fn ($record) => in_array($record->estado->value, ['creada', 'cotizacion', 'backorder'])),
                    Action::make('orden')
                        ->icon('heroicon-o-document-arrow-down')
                        ->hidden(fn ($record) => $record->estado->value == 'cotizacion')
                        ->modalContent(fn (Orden $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Orden #'.$record->id,
                                'route' => route('pdf.orden', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    Action::make('cotizacion')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn ($record) => $record->estado->value == 'cotizacion')
                        ->modalContent(fn (Orden $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Cotización #'.$record->id,
                                'route' => route('pdf.cotizacion', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    Action::make('Crear Orden')
                        ->icon('tabler-transform')
                        ->visible(fn ($record) => $record->estado->value == 'cotizacion')
                        ->action(fn (Orden $record, array $data) => OrdenController::cotizacionOrden($record)),
                    Action::make('receipt')
                        ->label('Recibo')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn ($record) => auth()->user()->can('receipt', $record))
                        ->modalContent(fn (Orden $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Recibo Orden #'.$record->id,
                                'route' => route('pdf.recibo-orden', ['id' => $record->id]),
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
                        ->modalContent(fn (Orden $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Factura Orden #'.$record->id,
                                'route' => route('pdf.factura.orden', ['id' => $record->id]),
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
                        ->modalContent(fn (Orden $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Factura Orden #'.$record->id,
                                'route' => route('pdf.nota-credito.orden', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    Action::make('confirm')
                        ->label('Confirmar')
                        ->color('success')
                        ->closeModalByClickingAway(false)
                        ->modalWidth(MaxWidth::Large)
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            Select::make('bodega_id')
                                ->label('Bodega')
                                ->searchable()
                                ->preload()
                                ->relationship('bodega', 'bodega', fn (Builder $query) => $query->whereHas('user', function ($query) {
                                    $query->where('user_id', auth()->user()->id);
                                }))
                                ->required(),
                        ])
                        ->action(fn (Orden $record, array $data) => OrdenController::confirmar($record, $data['bodega_id']))
                        ->visible(fn ($record) => auth()->user()->can('confirm', $record)),
                    Action::make('assign')
                        ->label('Asignar Bodega')
                        ->color('info')
                        ->closeModalByClickingAway(false)
                        ->modalWidth(MaxWidth::Large)
                        ->icon('tabler-asterisk')
                        ->form([
                            Select::make('bodega_id')
                                ->label('Bodega')
                                ->searchable()
                                ->preload()
                                ->relationship('bodega', 'bodega', fn (Builder $query) => $query->whereHas('user', function ($query) {
                                    $query->where('user_id', auth()->user()->id);
                                }))
                                ->required(),
                        ])
                        ->action(fn (Orden $record, array $data) => OrdenController::asignar($record, $data['bodega_id']))
                        ->visible(fn ($record) => auth()->user()->can('assign', $record)),
                    Action::make('send')
                        ->label('Enviar')
                        ->requiresConfirmation()
                        ->icon('tabler-send-2')
                        ->action(fn (Orden $record) => OrdenController::enviar($record))
                        ->visible(fn ($record) => auth()->user()->can('send', $record)),
                    Action::make('finish')
                        ->label('Finalizar')
                        ->requiresConfirmation()
                        ->color('success')
                        ->form([
                            TextInput::make('recibio')
                                ->label('Recibió')
                                ->required(),
                        ])
                        ->icon('heroicon-c-gift')
                        ->action(fn (array $data, Orden $record) => OrdenController::finalizar($data, $record))
                        ->visible(fn ($record) => auth()->user()->can('finish', $record)),
                    Action::make('cancelguide')
                        ->label('Volver A Preparar')
                        ->color('orange')
                        ->requiresConfirmation()
                        ->icon('tabler-player-skip-back-filled')
                        ->action(fn (Orden $record) => OrdenController::anularGuias($record))
                        ->visible(fn ($record) => auth()->user()->can('cancelguide', $record)),
                    Action::make('goback')
                        ->label('Regresar')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->icon('tabler-arrow-back-up-double')
                        ->action(fn (Orden $record) => OrdenController::regresar($record))
                        ->visible(fn ($record) => auth()->user()->can('goback', $record)),
                    Action::make('liquidate')
                        ->label('Liquidar')
                        ->color('success')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-clipboard-document-check')
                        ->action(fn (Orden $record) => OrdenController::liquidar($record))
                        ->visible(fn ($record) => auth()->user()->can('liquidate', $record)),
                    Action::make('annular')
                        ->label('Anular')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(fn (array $data, Orden $record) => OrdenController::anular($data, $record))
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
                        ->fillForm(fn (Orden $record): array => [
                            'costo_envio' => $record->costo_envio,
                            'motivo' => $record->motivo,
                            'apoyo' => $record->apoyo,
                        ])
                        ->form([
                            Grid::make(['default' => 1, 'md' => 6])
                                ->schema([
                                    Textarea::make('motivo')
                                        ->required()
                                        ->label('Motivo de Devolución')
                                        ->minLength(10)
                                        ->columnSpan(['default' => 1, 'md' => 4]),
                                    TextInput::make('costo_envio')
                                        ->label('Costo Envío')
                                        ->columnSpan(1)
                                        ->disabled(),
                                    TextInput::make('apoyo')
                                        ->label('Apoyo')
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->columnSpan(1)
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(),
                                ]),
                            Repeater::make('detalles')
                                ->label('')
                                ->collapsible()
                                ->relationship()
                                ->addable(false)
                                ->deletable(false)
                                ->schema([
                                    Select::make('producto_id')
                                        ->label('Producto')
                                        ->relationship('producto', 'descripcion', fn ($query) => $query->with(['marca', 'presentacion', 'escalas']))
                                        ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, '', 1))
                                        ->allowHtml()
                                        ->searchable(['id'])
                                        ->getSearchResultsUsing(function (string $search): array {
                                            return ProductoController::searchProductos($search, '', 1);
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
                                            TextInput::make('devuelto_mal')
                                                ->label('De lo Devuelto, Cuánto está Mal?')
                                                ->inputMode('decimal')
                                                ->rule('numeric')
                                                ->minValue(0)
                                                ->default(0)
                                                ->required(),
                                        ]),
                                ]),
                        ])
                        ->action(function (array $data, Orden $record): void {
                            OrdenController::devolver($data, $record);
                        }),
                ])
                    ->link()
                    ->label('Acciones'),
            ], position: ActionsPosition::BeforeColumns)
            ->poll('10s');
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
            'index' => Pages\ListOrdens::route('/'),
            'create' => Pages\CreateOrden::route('/create'),
            'view' => Pages\ViewOrden::route('/{record}'),
            'edit' => Pages\EditOrden::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasAnyRole(['administrador', 'super_admin', 'facturador', 'creditos', 'rrhh', 'gerente'])) {
            return $query;
        }

        if ($user->hasAnyRole(User::ORDEN_ROLES)) {
            $query->where('asesor_id', $user->id);
        }

        if ($user->hasAnyRole(User::SUPERVISORES_ORDEN)) {
            $supervisedIds = $user->asesoresSupervisados->pluck('id');

            $query->whereIn('asesor_id', $supervisedIds);
        }

        if ($user->hasAnyRole(['recolector', 'empaquetador', 'bodeguero'])) {
            $bodegaIds = $user->bodegas->pluck('id');

            $query->whereIn('bodega_id', $bodegaIds);
        }

        return $query;
    }

    public static function getNavigationItems(): array //  AÑADE ESTE MÉTODO
    {
        return [
            parent::getNavigationItems()[0] // Obtiene el elemento de navegación por defecto
                ->visible(false), //  Aplica ->visible(false) para ocultarlo SIEMPRE
        ];
    }
}
