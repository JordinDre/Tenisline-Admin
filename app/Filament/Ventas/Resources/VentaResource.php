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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
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
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

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
            'factura',
            'annular',
            'return',
            'facturar',
            'credit_note',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 2])
                    ->schema([
                        TextInput::make('subtotal')
                            ->prefix('Q')
                            ->label('SubTotal'),
                        TextInput::make('total')
                            ->prefix('Q')
                            ->label('Total'),
                    ]),
                Wizard::make([
                    Wizard\Step::make('Cliente')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('bodega_id')
                                        ->relationship(
                                            'bodega',
                                            'bodega'
                                        ),
                                    Select::make('cliente_id')
                                        ->label('Cliente')
                                        ->relationship(
                                            'cliente',
                                            'name',
                                        )
                                        ->optionsLimit(12)
                                        ->getOptionLabelFromRecordUsing(
                                            fn (User $record) => collect([
                                                $record->id,
                                                $record->nit ? $record->nit : 'CF',
                                                $record->name,
                                                $record->razon_social,
                                            ])->filter()->join(' - ')
                                        ),
                                ]),
                            Grid::make([
                                'default' => 1,
                                'sm' => 3,
                            ])
                                ->schema([
                                    Select::make('user_id')
                                        ->label('Vendedor')
                                        ->relationship('asesor', 'name'),
                                    DateTimePicker::make('created_at')
                                        ->label('Fecha de Creación')
                                        ->format('d/m/Y H:i:s'),
                                    Select::make('estado')
                                        ->options(EstadoVentaStatus::class),
                                ]),
                            Textarea::make('observaciones')
                                ->columnSpanFull(),
                        ]),
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
                                        ->relationship('producto', 'descripcion', function ($query) {
                                            $query->withTrashed();
                                        })
                                        ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, 'venta', $get('../../bodega_id')))
                                        ->allowHtml()
                                        ->searchable(['id'])
                                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                                            return ProductoController::searchProductos($search, 'venta', $get('../../bodega_id'));
                                        })
                                        ->optionsLimit(20)
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 4, 'xl' => 6])
                                        ->required(),
                                    TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    Select::make('escala_id')
                                        ->label('Escala')
                                        ->options(
                                            fn (Get $get) => Producto::find($get('producto_id'))?->escalas()->pluck('escala', 'id')
                                        )
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->placeholder('Escoger')
                                        ->required(),
                                    TextInput::make('precio')
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    Placeholder::make('subtotal')
                                        ->label('Subtotal')
                                        ->content(function (Get $get) {
                                            $escala = Escala::find($get('escala_id'));
                                            if ($escala) {
                                                return $escala->precio * (is_numeric($get('cantidad')) ? (float) $get('cantidad') : 0);
                                            }

                                            return 0;
                                        }),
                                ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Producto'),
                        ]),
                    Wizard\Step::make('Pagos')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 11,
                            ])
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Tipo de Pago')
                                        ->required()
                                        ->columnSpan(['sm' => 1, 'md' => 8])
                                        ->options(
                                            fn (Get $get) => User::find($get('cliente_id'))?->tipo_pagos->pluck('tipo_pago', 'id') ?? []
                                        ),
                                    Toggle::make('facturar_cf')
                                        ->inline(false)
                                        ->label('Facturar CF'),
                                    Toggle::make('comp')
                                        ->inline(false)
                                        ->label('Comp'),
                                    Toggle::make('pago_validado')
                                        ->label('Pago Validado')
                                        ->inline(false),
                                ]),
                            Repeater::make('pagos')
                                ->label('')
                                ->relationship()
                                ->columns(7)
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Forma de Pago')
                                        ->relationship('tipoPago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO))
                                        ->columnSpan(['sm' => 1, 'md' => 2]),
                                    TextInput::make('monto')
                                        ->label('Monto'),
                                    TextInput::make('no_documento')
                                        ->label('No. Documento'),
                                    TextInput::make('no_autorizacion')
                                        ->label('No. Autorización')
                                        ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null),
                                    TextInput::make('no_auditoria')
                                        ->label('No. Auditoría')
                                        ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null),
                                    TextInput::make('afiliacion')
                                        ->label('Afiliación')
                                        ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null),
                                    Select::make('cuotas')
                                        ->options([1 => 1, 3 => 3, 6 => 6, 9 => 9, 12 => 12])
                                        ->visible(fn (Get $get) => $get('tipo_pago_id') == 7 && $get('tipo_pago_id') != null),
                                    TextInput::make('nombre_cuenta')
                                        ->visible(fn (Get $get) => $get('tipo_pago_id') == 6 && $get('tipo_pago_id') != null),
                                    Select::make('banco_id')
                                        ->label('Banco')
                                        ->columnSpan(['sm' => 1, 'md' => 2])
                                        ->relationship('banco', 'banco'),
                                    DatePicker::make('fecha_transaccion'),
                                    FileUpload::make('imagen')
                                        ->image()
                                        ->downloadable()
                                        ->label('Imágen')
                                        ->disk(config('filesystems.disks.s3.driver'))
                                        ->directory(config('filesystems.default'))
                                        ->visibility('public')
                                        ->appendFiles()
                                        ->openable()
                                        ->columnSpan(['sm' => 1, 'md' => 3]),
                                ])->collapsible(),
                        ]),
                ])->skippable()->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('detalles.producto.nombre')
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
                    /* Action::make('nota_credito')
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
                        ->modalSubmitAction(false), */
                    Action::make('facturar')
                        ->label('Facturar')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-document-text')
                        ->color('indigo')
                        ->action(fn (Venta $record) => VentaController::facturar($record))
                        ->visible(fn ($record) => auth()->user()->can('facturar', $record)),
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
                                        ->relationship('producto', 'descripcion', fn ($query) => $query->with(['marca']))
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
                        ->action(function (array $data, Venta $record): void {
                            VentaController::devolver($data, $record);
                        }),
                ])
                    ->link()
                    ->label('Acciones'),
            ], position: ActionsPosition::BeforeColumns)
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
            'index' => Pages\ListVentas::route('/'),
            'create' => Pages\CreateVenta::route('/create'),
            'view' => Pages\ViewVenta::route('/{record}'),
            'edit' => Pages\EditVenta::route('/{record}/edit'),
        ];
    }

    /* public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasAnyRole(['administrador', 'super_admin', 'facturador', 'creditos', 'rrhh', 'gerente'])) {
            return $query;
        }

        if ($user->hasAnyRole(User::VENTA_ROLES)) {
            $query->where('asesor_id', $user->id);

            return $query;
        }

        if ($user->hasAnyRole(User::SUPERVISORES_ORDEN)) {
            $supervisedIds = $user->asesoresSupervisados->pluck('id');
            $query->whereIn('asesor_id', $supervisedIds);

            return $query;
        }
    } */
}
