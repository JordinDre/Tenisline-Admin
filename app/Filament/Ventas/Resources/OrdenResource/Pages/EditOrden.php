<?php

namespace App\Filament\Ventas\Resources\OrdenResource\Pages;

use App\Enums\EnvioStatus;
use App\Enums\EstadoOrdenStatus;
use App\Filament\Ventas\Resources\OrdenResource;
use App\Http\Controllers\GUATEXController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UserController;
use App\Models\Direccion;
use App\Models\Escala;
use App\Models\Factura;
use App\Models\Guia;
use App\Models\Inventario;
use App\Models\Orden;
use App\Models\Producto;
use App\Models\TipoPago;
use App\Models\User;
use Closure;
use Filament\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class EditOrden extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = OrdenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 3])
                    ->schema([
                        TextInput::make('subtotal')
                            ->prefix('Q')
                            ->readOnly()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $subtotal = 0;
                                    $cantidadCubetasCanecas = 0;
                                    $cantidadProductos = 0;
                                    $detalles = $get('detalles') ?? [];
                                    if (! is_array($detalles) || empty($detalles)) {
                                        $fail('No hay detalles en la orden.');

                                        return;
                                    }
                                    foreach ($detalles as $detalle) {
                                        $productoId = $detalle['producto_id'] ?? null;
                                        $cantidad = $detalle['cantidad'] ?? 0;
                                        $precio = $detalle['precio'] ?? 0;
                                        if (! is_numeric($cantidad) || ! is_numeric($precio)) {
                                            $fail('La cantidad o el precio de algún detalle no son válidos.');

                                            return;
                                        }
                                        $producto = Producto::withTrashed()->find($productoId);
                                        if (! $producto) {
                                            $fail("El producto con ID {$productoId} no existe.");

                                            return;
                                        }
                                        $presentacion = strtolower($producto->presentacion ?? '');
                                        if (str_contains($presentacion, 'cubeta') || str_contains($presentacion, 'caneca')) {
                                            $cantidadCubetasCanecas += $cantidad;
                                        } else {
                                            $subtotal += $cantidad * $precio;
                                            $cantidadProductos += $cantidad;
                                        }
                                    }
                                    if ($cantidadCubetasCanecas > 0 && $cantidadProductos > 0 && $subtotal < 100) {
                                        $fail('La Orden debe tener un SubTotal mayor a Q100.00 para los productos que no son Canecas o Cubetas.');
                                    }

                                    if ($value < 100) {
                                        $fail('La Orden debe tener un SubTotal mayor a Q100.00');
                                    }
                                },
                            ])

                            ->label('SubTotal'),
                        TextInput::make('envio')
                            ->default(Guia::ENVIO)
                            ->prefix('Q')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $subtotal = (float) $get('subtotal');
                                $envio = (float) $get('envio');
                                $set('total', round($subtotal + $envio, 2));
                            })
                            ->minValue(0)
                            ->label('Envío'),
                        TextInput::make('total')
                            ->readOnly()
                            ->prefix('Q')
                            ->rules([
                                fn (Get $get, Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $user = User::find($get('cliente_id'));
                                    if ($get('tipo_pago_id') == 2 && $value > (($user->credito + $record->total) - $user->saldo)) {
                                        $fail('El Cliente no cuenta con suficiente crédito para realizar la compra.');
                                    }
                                    if ($get('tipo_pago_id') == 3 && $value > 5000) {
                                        $fail('El Pago Contra Entrega no puede superar los Q5,000.00');
                                    }
                                    if ($user && in_array($user->nit, [null, '', 'CF', 'cf', 'cF', 'Cf'], true) && $value >= Factura::CF) {
                                        $fail('El Cliente no cuenta con NIT registrado para el valor de la Orden.');
                                    }
                                },
                            ])
                            ->label('Total'),
                    ]),
                Wizard::make([
                    Wizard\Step::make('Productos')
                        ->disabled(fn ($record) => auth()->user()->can('products', $record) && $record->estado->value != 'cotizacion')
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
                                        ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, 'orden', 1))
                                        ->allowHtml()
                                        ->searchable(['id'])
                                        ->getSearchResultsUsing(function (string $search): array {
                                            return ProductoController::searchProductos($search, 'orden', 1);
                                        })
                                        ->optionsLimit(20)
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 4, 'xl' => 6])
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state) {
                                                $userRoles = User::find($get('../../asesor_id'))->roles->pluck('name');
                                                $role = collect(User::ORDEN_ROLES)->first(fn ($r) => $userRoles->contains($r));
                                                $escala = Escala::where('producto_id', $state)
                                                    ->whereHas('role', fn ($q) => $q->where('name', $role))
                                                    ->orderByDesc('precio')
                                                    ->first();
                                                if ($escala) {
                                                    $set('escala_id', $escala->id);
                                                    $set('precio', $escala->precio);
                                                    $set('comision', $escala->comision);
                                                    $set('precio_comp', Producto::find($state)->precio_costo);
                                                    $set('subtotal', round((float) $escala->precio * (float) $get('cantidad'), 2));
                                                    $set('ganancia', round((float) $escala->precio * (float) $get('cantidad') * ($escala->comision / 100), 2));

                                                    return;
                                                }
                                            }
                                            $set('escala_id', null);
                                            $set('precio', 0);
                                            $set('comision', 0);
                                            $set('precio_comp', null);
                                            $set('subtotal', 0);
                                            $set('ganancia', 0);
                                        })
                                        ->suffixAction(
                                            Action::make('ver')
                                                ->icon('heroicon-s-eye')
                                                ->modalContent(fn ($state): View => view(
                                                    'filament.pages.actions.producto',
                                                    [
                                                        'url' => Producto::find($state)?->imagenes[0]
                                                            ? config('filesystems.disks.s3.url').Producto::find($state)->imagenes[0]
                                                            : null,
                                                        'alt' => Producto::find($state)?->descripcion ?? 'Sin descripción',
                                                    ],
                                                ))
                                                ->modalSubmitAction(false)
                                                ->modalWidth(MaxWidth::Screen)
                                        )
                                        ->required(),
                                    TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->default(1)
                                        ->minValue(1)
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $set('subtotal', round((float) $state * (float) $get('precio'), 2));
                                            $set('ganancia', round((float) $state * (float) $get('precio') * ($get('comision') / 100), 2));
                                        })
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->required(),
                                    TextInput::make('precio')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state) {
                                                $userRoles = User::find($get('../../asesor_id'))->roles->pluck('name');
                                                $role = collect(User::ORDEN_ROLES)->first(fn ($r) => $userRoles->contains($r));
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
                                        })
                                        ->label('Precio')
                                        ->default(0)
                                        ->required()
                                        ->prefix('Q')
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->minValue(function (Get $get) {
                                            if (! auth()->user()->can('products_orden')) {
                                                $userRoles = User::find($get('../../asesor_id'))->roles->pluck('name');
                                                $role = collect(User::ORDEN_ROLES)->first(fn ($r) => $userRoles->contains($r));

                                                return Escala::where('producto_id', $get('producto_id'))
                                                    ->whereHas('role', fn ($q) => $q->where('name', $role))
                                                    ->orderBy('precio')
                                                    ->first()->precio;
                                            }
                                        })
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    TextInput::make('comision')
                                        ->label('Comisión (%)')
                                        ->readOnly()
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    Select::make('escala_id')
                                        ->label('Escala')
                                        ->options(
                                            fn (Get $get) => Producto::withTrashed()->find($get('producto_id'))?->escalas()->pluck('escala', 'id')
                                        )
                                        ->disabled()
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->placeholder('Escoger')
                                        ->required(),
                                    Hidden::make('precio_comp'),
                                    Hidden::make('ganancia'),
                                    TextInput::make('subtotal')
                                        ->label('SubTotal')
                                        ->prefix('Q')
                                        ->default(0)
                                        ->readOnly()
                                        ->columnSpan(['default' => 2,  'md' => 3, 'lg' => 4, 'xl' => 2]),
                                ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Producto')
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, Model $record) {
                                    $detalles = $get('detalles');
                                    $subtotal = collect($detalles)->sum(function ($detalle) {
                                        $escala = Escala::find($detalle['escala_id']);
                                        if ($escala) {
                                            $precio = is_numeric($detalle['precio']) ? (float) $detalle['precio'] : (float) $escala->precio;
                                            $cantidad = is_numeric($detalle['cantidad']) ? (float) $detalle['cantidad'] : 0;

                                            return $precio * $cantidad;
                                        }

                                        return 0;
                                    });
                                    $set('subtotal', round($subtotal, 2));
                                    $get('subtotal') < Guia::ENVIO_GRATIS ? $set('envio', Guia::ENVIO) : $set('envio', 0);
                                    $get('subtotal') >= Factura::CF || $set('facturar_cf', false);
                                    $get('subtotal') >= Factura::CF || $set('comp', false);
                                    $set('total', round($subtotal + $get('envio'), 2));
                                }),
                        ]),
                    Wizard\Step::make('Cliente')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 2,
                            ])
                                ->schema([
                                    Select::make('cliente_id')
                                        ->label('Cliente')
                                        ->disabled(fn ($record) => $record->estado->value != 'cotizacion')
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
                                    DateTimePicker::make('created_at')
                                        ->label('Fecha de Creación')
                                        ->format('d/m/Y H:i:s')
                                        ->disabled(),
                                ]),
                            Select::make('direccion_id')
                                ->label('Dirección')
                                ->required()
                                ->disabled(fn ($record) => $record->estado->value != 'cotizacion')
                                ->options(fn (Get $get) => User::find($get('cliente_id'))?->direcciones->mapWithKeys(function ($direccion) {
                                    return [
                                        $direccion->id => collect([
                                            $direccion->direccion ?? '',
                                            $direccion->referencia ?? '',
                                            $direccion->zona ?? '',
                                            $direccion->municipio->municipio ?? '',
                                            $direccion->departamento->departamento ?? '',
                                        ])->filter()->join(' - '),
                                    ];
                                })),
                            Grid::make([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 3,
                                'lg' => 7,
                            ])
                                ->schema([
                                    Select::make('asesor_id')
                                        ->disabled()
                                        ->relationship(
                                            'asesor',
                                            'name'
                                        ),
                                    DatePicker::make('prefechado')
                                        ->minDate(today())
                                        ->maxDate(today()->endOfMonth())
                                        ->visible(fn ($record) => $record->estado->value != 'cotizacion')
                                        ->label('Prefechado'),
                                    DatePicker::make('prefechado')
                                        ->label('Prefechado')
                                        ->visible(fn ($record) => $record->estado->value == 'cotizacion')
                                        ->minDate(now()->addDay()->startOfDay())
                                        ->maxDate(min(now()->addDays(7), now()->endOfMonth()->startOfDay())),
                                    Select::make('estado')
                                        ->options(EstadoOrdenStatus::class)
                                        ->disabled()
                                        ->required(),
                                    Select::make('tipo_envio')
                                        ->label('Tipo de Envío')
                                        ->options(EnvioStatus::class)
                                        ->default('guatex')
                                        ->disabled(fn ($record) => $record->estado->value != 'cotizacion')
                                        ->required(),
                                    Select::make('guatex_destino')
                                        ->label('Destino')
                                        ->visible(fn (Get $get) => $get('tipo_envio') == 'guatex')
                                        ->preload()
                                        ->required()
                                        ->disabled(! auth()->user()->can('products_orden'))
                                        ->searchable()
                                        ->columnSpan(['default' => 1, 'lg' => 3])
                                        ->options(function (Get $get) {
                                            $direccion = Direccion::find($get('direccion_id'));
                                            if ($direccion && $direccion->municipio) {
                                                $departamento = $direccion->municipio->departamento->departamento;
                                                $municipio = $direccion->municipio->municipio;

                                                $guatexController = new GUATEXController;
                                                $destinos = auth()->user()->hasAnyRole(User::ROLES_ADMIN)
                                                    ? $guatexController->obtenerDestinos($departamento)
                                                    : $guatexController->obtenerDestinos($departamento, $municipio);

                                                return collect($destinos)->mapWithKeys(function ($destino) {
                                                    return [$destino['texto'] => $destino['texto']];
                                                })->toArray();
                                            }

                                            return [];
                                        }),
                                ]),
                            Textarea::make('observaciones')
                                ->disabled(fn ($record) => $record->estado->value != 'cotizacion')
                                ->columnSpanFull(),
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
                                        ->disabled(fn ($record) => $record->estado->value != 'cotizacion')
                                        ->columnSpan(['sm' => 1, 'md' => 8])
                                        ->options(
                                            fn (Get $get) => User::find($get('cliente_id'))?->tipo_pagos->pluck('tipo_pago', 'id') ?? []
                                        )
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                    Toggle::make('facturar_cf')
                                        ->inline(false)
                                        ->live()
                                        ->disabled(fn (Get $get, $record) => $get('total') >= Factura::CF)
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            if (! $get('facturar_cf')) {
                                                $set('comp', false);
                                            }
                                        })
                                        ->label('Facturar CF'),
                                    Toggle::make('comp')
                                        ->inline(false)
                                        ->label('Comp')
                                        ->disabled(fn (Get $get, $record) => $get('facturar_cf') == false || $get('total') >= Factura::CF),
                                    Toggle::make('pago_validado')
                                        ->label('Pago Validado')
                                        ->visible(fn ($record) => auth()->user()->can('validate_pay', $record))
                                        ->inline(false),
                                ]),
                            Repeater::make('pagos')
                                ->label('')
                                ->relationship()
                                ->disabled(fn ($record) => $record->estado->value != 'cotizacion')
                                ->columns(7)
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Forma de Pago')
                                        ->relationship('tipoPago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO))
                                        ->columnSpan(['sm' => 1, 'md' => 2]),
                                    TextInput::make('monto')
                                        ->label('Monto')
                                        ->prefix('Q')
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            $set('total', $get('monto'));
                                        }),
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
                                        ->visible(fn (Get $get) => $get('tipo_pago_id') == 6 && $get('tipo_pago_id') != null)
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
                                        ->maxSize(5000)
                                        ->resize(50)
                                        ->openable()
                                        ->columnSpan(['sm' => 1, 'md' => 3])
                                        ->optimize('webp')
                                        ->required(),
                                ])
                                ->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Pago'),
                        ]),
                ])->skippable()->columnSpanFull(),
            ]);
    }

    /* protected function afterValidate(): void
    {
        try {
            foreach ($this->data['detalles'] as $key => $detalle) {
                $escala = Escala::find($detalle['escala_id']);
                if ($escala->desde > $this->data['total'] || $escala->hasta < $this->data['total']) {
                    $producto = Producto::fwithTrashed()->find($detalle['producto_id']);
                    $productoDetalles = "{$producto->id} - {$producto->codigo} - {$producto->descripcion} - {$producto->marca->marca} - {$producto->presentacion->presentacion}";
                    throw new \Exception("El producto {$productoDetalles} no tiene una escala válida.");
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->warning()
                ->color('warning')
                ->title('Advertencia')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            $this->halt();
        }
    } */

    protected function beforeSave(): void
    {
        try {
            if ($this->data['tipo_pago_id'] == 2 && $this->data['estado'] != 'cotizacion') {
                UserController::restarSaldo(User::find($this->data['cliente_id']), Orden::find($this->data['id'])->total);
            }
        } catch (\Exception $e) {
            Notification::make()
                ->warning()
                ->color('warning')
                ->title('Advertencia')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        try {
            if ($this->record->estado->value != 'cotizacion') {
                if ($this->record->tipo_pago_id == 2) {
                    UserController::sumarSaldo(User::find($this->record->cliente_id), $this->record->total);
                }
                $productosSinExistencia = collect($this->record->detalles)->filter(function ($detalle) {
                    $existencia = Inventario::where('producto_id', $detalle->producto_id)
                        ->where('bodega_id', 1)
                        ->first()
                        ->existencia ?? 0;

                    return $existencia < $detalle->cantidad;
                });

                if ($productosSinExistencia->isEmpty()) {
                    OrdenController::confirmar($this->record, 1);
                } else {
                    $this->record->update(['estado' => 'backorder']);
                    Notification::make()
                        ->title('Orden en Backorder')
                        ->body('Algunos productos no tienen suficiente existencia')
                        ->info()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->warning()
                ->color('warning')
                ->title('Advertencia')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            $this->halt();
        }
    }
}
