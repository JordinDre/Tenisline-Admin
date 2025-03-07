<?php

namespace App\Filament\Ventas\Resources\OrdenResource\Pages;

use App\Enums\EnvioStatus;
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
use App\Models\Pago;
use App\Models\Producto;
use App\Models\TipoPago;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CreateOrden extends CreateRecord
{
    protected static string $resource = OrdenResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $subtotal = 0;
                                    $cantidadCubetasCanecas = 0;
                                    $cantidadProductos = 0;

                                    $detalles = $get('detalles') ?? [];
                                    if (! is_array($detalles) || empty($detalles)) {
                                        $fail('Los detalles de la orden no están definidos.');

                                        return;
                                    }
                                    foreach ($detalles as $detalle) {
                                        $productoId = $detalle['producto_id'] ?? null;
                                        $cantidad = $detalle['cantidad'] ?? 0;
                                        $precio = $detalle['precio'] ?? 0;
                                        if (! is_numeric($cantidad) || ! is_numeric($precio)) {
                                            $fail('Algunos detalles no tienen valores válidos para cantidad o precio.');

                                            return;
                                        }
                                        $producto = Producto::find($productoId);
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
                            ->readOnly()
                            ->live()
                            ->label('Envío'),
                        TextInput::make('total')
                            ->readOnly()
                            ->prefix('Q')
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $user = User::find($get('cliente_id'));
                                    if ($get('tipo_pago_id') == 2 && $value > ($user->credito - $user->saldo) && $get('estado') != 'cotizacion') {
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
                                        ->getOptionLabelFromRecordUsing(fn(Producto $record) => ProductoController::renderProductos($record, 'orden', 1))
                                        ->allowHtml()
                                        ->searchable(['id'])
                                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                                            return ProductoController::searchProductos($search, 'orden', 1);
                                        })
                                        ->optionsLimit(20)
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 4, 'xl' => 6])
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state) {
                                                $userRoles = auth()->user()->roles->pluck('name');
                                                $role = collect(User::ORDEN_ROLES)->first(fn($r) => $userRoles->contains($r));
                                                $escala = Escala::where('producto_id', $state)
                                                    ->whereHas('role', fn($q) => $q->where('name', $role))
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
                                                ->modalContent(fn($state): View => view(
                                                    'filament.pages.actions.producto',
                                                    [
                                                        'url' => Producto::find($state)?->imagenes[0]
                                                            ? config('filesystems.disks.s3.url') . Producto::find($state)->imagenes[0]
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
                                        ->label('Precio')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state) {
                                                $userRoles = auth()->user()->roles->pluck('name');
                                                $role = collect(User::ORDEN_ROLES)->first(fn($r) => $userRoles->contains($r));
                                                $escala = Escala::where('precio', '<', $state)
                                                    ->where('producto_id', $get('producto_id'))
                                                    ->whereHas('role', fn($q) => $q->where('name', $role))
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
                                        ->default(0)
                                        ->required()
                                        ->prefix('Q')
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->minValue(function (Get $get) {
                                            $userRoles = auth()->user()->roles->pluck('name');
                                            $role = collect(User::ORDEN_ROLES)->first(fn($r) => $userRoles->contains($r));

                                            return Escala::where('producto_id', $get('producto_id'))
                                                ->whereHas('role', fn($q) => $q->where('name', $role))
                                                ->orderBy('precio')
                                                ->first()->precio;
                                        })
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    TextInput::make('comision')
                                        ->label('Comisión (%)')
                                        ->readOnly()
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    Hidden::make('escala_id'),
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
                                ->afterStateUpdated(function (Set $set, Get $get) {
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
                            Select::make('cliente_id')
                                ->label('Cliente')
                                ->relationship(
                                    'cliente',
                                    'name',
                                    fn(Builder $query) => $query->whereIn('users.id', auth()->user()->clientes()->pluck('users.id'))
                                )
                                ->optionsLimit(12)
                                ->getOptionLabelFromRecordUsing(
                                    fn(User $record) => collect([
                                        $record->id,
                                        $record->nit ? $record->nit : 'CF',
                                        $record->name,
                                        $record->razon_social,
                                        $record->ordenes()->whereIn('estado', ['enviada'])->count() > 0 ? '(Ordenes Pendientes de Entrega) ' : '',
                                        ($record->creditosOrdenesAtrasados->count() > 0 || $record->creditosVentasAtrasados->count() > 0)
                                            ? '(Créditos Atrasados, Mínimo a Cancelar: Q' . $record->creditosOrdenesAtrasados->sum(fn($orden) => $orden->total - $orden->pagos->sum('monto')) +
                                            $record->creditosVentasAtrasados->sum(fn($venta) => $venta->total - $venta->pagos->sum('monto')) . ')'
                                            : '',
                                    ])->filter()->join(' - ')
                                )
                                ->disableOptionWhen(function (string $value): bool {
                                    return User::find($value)->ordenes()->whereIn('estado', ['enviada'])->count() > 0 || User::find($value)->creditosOrdenesAtrasados()->count() > 0
                                        || User::find($value)->creditosVentasAtrasados()->count() > 0;
                                })
                                ->suffixAction(
                                    Action::make('historial')
                                        ->label('Historial')
                                        ->modalWidth(MaxWidth::FiveExtraLarge)
                                        ->icon('tabler-history')
                                        ->slideOver()
                                        ->modalSubmitAction(false)
                                        ->modalContent(fn($state): View => view(
                                            'filament.pages.actions.historial',
                                            [
                                                'ordenes' => User::find($state)->ordenes,
                                            ],
                                        ))
                                )
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('tipo_pago_id', null);
                                    $set('guatex_destino', null);
                                })
                                ->searchable(),
                            Select::make('direccion_id')
                                ->required()
                                ->label('Dirección')
                                ->options(fn(Get $get) => User::find($get('cliente_id'))?->direcciones->mapWithKeys(function ($direccion) {
                                    return [
                                        $direccion->id => collect([
                                            $direccion->direccion ?? '',
                                            $direccion->referencia ?? '',
                                            $direccion->zona ?? '',
                                            $direccion->municipio->municipio ?? '',
                                            $direccion->departamento->departamento ?? '',
                                        ])->filter()->join(' - '),
                                    ];
                                }))
                                ->preload(),
                            Grid::make([
                                'default' => 1,
                                'sm' => 2,
                                'lg' => 6,
                            ])
                                ->schema([
                                    DatePicker::make('prefechado')
                                        ->label('Prefechado')
                                        ->minDate(now()->addDay()->startOfDay())
                                        ->maxDate(min(now()->addDays(7), now()->endOfMonth()->startOfDay())),
                                    Select::make('estado')
                                        ->label('Tipo')
                                        ->options([
                                            'creada' => 'Orden',
                                            'cotizacion' => 'Cotización',
                                        ])->default('creada')
                                        ->required(),
                                    Select::make('tipo_envio')
                                        ->label('Tipo de Envío')
                                        ->options(EnvioStatus::class)
                                        ->default('guatex')
                                        ->live()
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('guatex_destino', null);
                                        })
                                        ->required(),
                                    Select::make('guatex_destino')
                                        ->label('Destino')
                                        ->visible(fn(Get $get) => $get('tipo_envio') == 'guatex')
                                        ->preload()
                                        ->required()
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
                                ->columnSpanFull(),
                        ]),
                    Wizard\Step::make('Pagos')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 10,
                            ])
                                ->schema([
                                    Select::make('tipo_pago_id')
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
                                        ->rules([
                                            fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                if ($get('total') < collect($get('pagos'))->sum('monto') && $value == 4) {
                                                    $fail('El monto total de los pagos no puede ser mayor al total de la orden.');
                                                }
                                            },
                                        ])
                                        ->disableOptionWhen(function (string $value, Get $get): bool {
                                            $cliente = User::find($get('cliente_id'));
                                            if (! $cliente) {
                                                return false;
                                            }
                                            $ultimasOrdenes = $cliente->ordenes()
                                                ->orderByDesc('created_at')
                                                ->take(3)
                                                ->pluck('estado');
                                            $tieneDevoluciones = $ultimasOrdenes->filter(function ($estado) {
                                                return in_array($estado->value, ['devuelta', 'parcialmente devuelta']);
                                            })->count() > 0;
                                            if ($tieneDevoluciones) {
                                                return $value == 3;
                                            } else {
                                                return $value == 12;
                                            }
                                        })
                                        ->searchable()
                                        ->preload(),
                                    Toggle::make('facturar_cf')
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
                                        ->disabled(fn(Get $get) => $get('facturar_cf') == false || $get('total') >= Factura::CF),
                                ]),
                            Repeater::make('pagos')
                                ->label('')
                                ->relationship()
                                ->minItems(function (Get $get) {
                                    return $get('tipo_pago_id') == 4 ? 1 : 0;
                                })
                                ->visible(fn(Get $get) => $get('tipo_pago_id') == 4)
                                ->defaultItems(0)
                                ->columns(7)
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Forma de Pago')
                                        ->relationship('tipoPago', 'tipo_pago', fn(Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO))
                                        ->required()
                                        ->live()
                                        ->columnSpan(['sm' => 1, 'md' => 2])
                                        ->searchable()
                                        ->preload(),
                                    TextInput::make('monto')
                                        ->label('Monto')
                                        ->prefix('Q')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            $set('total', $get('monto'));
                                        })
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->minValue(1)
                                        ->required(),
                                    Hidden::make('total'),
                                    Hidden::make('user_id')
                                        ->default(auth()->user()->id),
                                    TextInput::make('no_documento')
                                        ->label('No. Documento')
                                        ->rules([
                                            fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
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
                                        ->required(),
                                    TextInput::make('no_autorizacion')
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
                                        ->preload(),
                                    DatePicker::make('fecha_transaccion')
                                        ->default(now())
                                        ->required(),
                                    FileUpload::make('imagen')
                                        ->image()
                                        ->downloadable()
                                        ->label('Imágen')
                                        ->imageEditor()
                                        ->disk(config('filesystems.disks.s3.driver'))
                                        ->directory(config('filesystems.default'))
                                        ->visibility('public')
                                        ->appendFiles()
                                        ->maxSize(1024)
                                        ->openable()
                                        ->columnSpan(['sm' => 1, 'md' => 3])
                                        ->optimize('webp')
                                        ->required(),
                                ])
                                ->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Pago'),
                        ]),
                ])->columnSpanFull()->skippable(),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['asesor_id'] = auth()->user()->id;

        return $data;
    }

    /* protected function afterValidate(): void
    {
        try {
            // Obtener todos los registros necesarios en una sola consulta
           
            $productos = Producto::findMany(array_column($this->data['detalles'], 'producto_id'));
            $escalas = Escala::findMany(array_column($this->data['detalles'], 'escala_id'));

            foreach ($this->data['detalles'] as $key => $detalle) {
                // Obtener la escala asociada al detalle
                $escala = $escalas->firstWhere('id', $detalle['escala_id']);
                if ($escala->desde > $this->data['total'] || $escala->hasta < $this->data['total']) {
                    // Obtener el producto correspondiente al detalle
                    $producto = $productos->firstWhere('id', $detalle['producto_id']);
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

    protected function afterCreate(): void
    {
        try {
            if ($this->record->estado->value != 'cotizacion') {
                if ($this->record->tipo_pago_id == 2) {
                    UserController::sumarSaldo(User::find($this->record->cliente_id), $this->record->total);
                }

                if (! $this->record->observaciones) {
                    // Obtener los productos asociados a la orden
                    $productos = collect($this->record->detalles)->pluck('producto_id')->toArray();

                    // Obtener los inventarios de una sola vez
                    $inventarios = Inventario::whereIn('producto_id', $productos)
                        ->where('bodega_id', 1)
                        ->get()
                        ->keyBy('producto_id');

                    $productosSinExistencia = collect($this->record->detalles)->filter(function ($detalle) use ($inventarios) {
                        $inventario = $inventarios->get($detalle->producto_id);
                        $existencia = $inventario ? $inventario->existencia : 0;

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

                if (auth()->user()->asignado_id == $this->record->cliente_id) {
                    auth()->user()->update(['asignado_id' => null]);
                }
            }
            $this->form->fill();
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
