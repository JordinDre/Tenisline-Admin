<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use Closure;
use App\Models\Pago;
use App\Models\User;
use App\Models\Banco;
use App\Models\Venta;
use App\Models\Cierre;
use App\Models\Escala;
use App\Models\Factura;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Producto;
use App\Models\TipoPago;
use Filament\Forms\Form;
use App\Models\Municipio;
use Illuminate\Support\Str;
use App\Models\Departamento;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use App\Http\Controllers\UserController;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Http\Controllers\VentaController;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;
use App\Http\Controllers\ProductoController;
use Illuminate\Validation\ValidationException;
use App\Filament\Ventas\Resources\VentaResource;
use App\Filament\Traits\ManageDiscountLogic;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    use ManageDiscountLogic;

    protected $subtotalOriginal;

    public function form(Form $form): Form
    {
        return $form
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
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('detalles', []);
                    })
                    ->searchable()
                    ->required(),
                Wizard::make([
                    Wizard\Step::make('Cliente y Productos')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 18,
                            ])
                                ->schema([
                                    Select::make('cliente_id')
                                        ->label('Cliente')
                                        ->relationship('cliente', 'name', fn (Builder $query) => $query->role(['cliente', 'cliente_apertura', 'colaborador']))
                                        ->optionsLimit(20)
                                        ->required()
                                        ->live()
                                        ->reactive()
                                        ->columnSpan(['sm' => 1, 'md' => 15])
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                if (! $get('facturar_cf')) {
                                                    $cliente = User::find($value);
                                                    $nit = trim($cliente->nit ?? '');

                                                    if (
                                                        empty($nit) ||
                                                        in_array(strtolower($nit), ['cf']) ||
                                                        ! preg_match('/\d/', $nit) // no contiene ningún número
                                                    ) {
                                                        $fail('El NIT del cliente es inválido para facturar.');
                                                    }
                                                }
                                            },
                                        ])
                                        ->createOptionForm([
                                            TextInput::make('nit')
                                                ->default('CF')
                                                ->required()
                                                ->maxLength(25)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, $state) {
                                                    $nit = UserController::nit($state);
                                                    $set('razon_social', $nit);
                                                }),
                                            TextInput::make('razon_social')
                                                ->required()
                                                ->readOnly()
                                                ->default('CF')
                                                ->label('Razón Social'),
                                            TextInput::make('name')
                                                ->required()
                                                ->unique(table: User::class)
                                                ->label('Nombre/Nombre Comercial'),
                                            TextInput::make('telefono')
                                                ->label('Teléfono')
                                                ->tel()
                                                ->required()
                                                ->minLength(8)
                                                ->maxLength(8)
                                                ->unique(table: User::class, column: 'telefono'),
                                            TextInput::make('whatsapp')
                                                ->label('WhatsApp')
                                                ->tel()
                                                ->minLength(8)
                                                ->maxLength(8),
                                            Repeater::make('direcciones')
                                                ->relationship()
                                                ->schema([
                                                    Select::make('pais_id')
                                                        ->relationship('pais', 'pais')
                                                        ->required()
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function (Set $set) {
                                                            $set('departamento_id', null);
                                                            $set('municipio_id', null);
                                                        })
                                                        ->default(1)
                                                        ->searchable()
                                                        ->preload(),
                                                    Select::make('departamento_id')
                                                        ->label('Departamento')
                                                        ->options(fn (Get $get) => Departamento::where('pais_id', $get('pais_id'))->pluck('departamento', 'id'))
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function (Set $set) {
                                                            $set('municipio_id', null);
                                                        })
                                                        ->required()
                                                        ->searchable()
                                                        ->preload(),
                                                    Select::make('municipio_id')
                                                        ->label('Municipio')
                                                        ->options(fn (Get $get) => Municipio::where('departamento_id', $get('departamento_id'))->pluck('municipio', 'id'))
                                                        ->required()
                                                        ->searchable()
                                                        ->preload(),
                                                    TextInput::make('direccion')
                                                        ->required()
                                                        ->label('Dirección')
                                                        ->maxLength(255),
                                                    TextInput::make('referencia')
                                                        ->required()
                                                        ->maxLength(255),
                                                    TextInput::make('zona')
                                                        ->label('Zona')
                                                        ->inputMode('decimal')
                                                        ->rule('numeric')
                                                        ->minValue(0),
                                                ])->columnSpanFull()->columns(3)->defaultItems(0),
                                        ])
                                        /* ->editOptionForm([
                                            TextInput::make('nit')
                                                ->default('CF')
                                                ->required()
                                                ->maxLength(25)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, $state) {
                                                    $nit = UserController::nit($state);
                                                    $set('razon_social', $nit);
                                                }),
                                            TextInput::make('razon_social')
                                                ->required()
                                                ->readOnly()
                                                ->default('CF')
                                                ->label('Razón Social'),
                                            TextInput::make('name')
                                                ->required()
                                                ->unique(table: User::class)
                                                ->label('Nombre/Nombre Comercial'),
                                            TextInput::make('telefono')
                                                ->label('Teléfono')
                                                ->tel()
                                                ->required()
                                                ->minLength(8)
                                                ->maxLength(8),
                                            TextInput::make('whatsapp')
                                                ->label('WhatsApp')
                                                ->tel()
                                                ->minLength(8)
                                                ->maxLength(8),
                                            Repeater::make('direcciones')
                                                ->relationship()
                                                ->schema([
                                                    Select::make('pais_id')
                                                        ->relationship('pais', 'pais')
                                                        ->required()
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function (Set $set) {
                                                            $set('departamento_id', null);
                                                            $set('municipio_id', null);
                                                        })
                                                        ->default(1)
                                                        ->searchable()
                                                        ->preload(),
                                                    Select::make('departamento_id')
                                                        ->label('Departamento')
                                                        ->options(fn (Get $get) => Departamento::where('pais_id', $get('pais_id'))->pluck('departamento', 'id'))
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function (Set $set) {
                                                            $set('municipio_id', null);
                                                        })
                                                        ->required()
                                                        ->searchable()
                                                        ->preload(),
                                                    Select::make('municipio_id')
                                                        ->label('Municipio')
                                                        ->options(fn (Get $get) => Municipio::where('departamento_id', $get('departamento_id'))->pluck('municipio', 'id'))
                                                        ->required()
                                                        ->searchable()
                                                        ->preload(),
                                                    TextInput::make('direccion')
                                                        ->required()
                                                        ->label('Dirección')
                                                        ->maxLength(255),
                                                    TextInput::make('referencia')
                                                        ->required()
                                                        ->maxLength(255),
                                                    TextInput::make('zona')
                                                        ->label('Zona')
                                                        ->inputMode('decimal')
                                                        ->rule('numeric')
                                                        ->minValue(0),
                                                ])->columnSpanFull()->columns(3)->defaultItems(0),
                                        ]) */
                                        ->createOptionUsing(function (array $data): int {
                                            $user = User::create($data);
                                            $user->assignRole('cliente'); // Asigna el rol automáticamente

                                            return $user->id; // Devuelve el ID para que se seleccione en el campo
                                        })
                                        ->searchable(),
                                    Toggle::make('facturar_cf')
                                        ->inline(false)
                                        ->live()
                                        ->disabled(fn (Get $get) => $get('total') >= Factura::CF)
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            if (! $get('facturar_cf')) {
                                                $set('comp', false);
                                            }
                                        })
                                        ->label('Facturar CF')
                                        ->columnSpan(3),
                                    Toggle::make('aplicar_descuento')
                                        ->label('Promo ')
                                        ->inline(false)
                                        ->dehydrated(false)
                                        ->visible(fn (Get $get): bool => ! empty($get('bodega_id')))
                                        ->reactive()
                                        ->afterStateUpdated(fn ($state, Get $get, Set $set) => $this->handleGlobalDiscountToggle($state, $get, $set)),
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
                                            Hidden::make('uuid')
                                                ->default(fn () => (string) Str::uuid())
                                                ->dehydrated(false),
                                            Toggle::make('oferta_20')
                                                ->label('20 %')
                                                ->inline(false)
                                                ->live()
                                                ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 1])
                                                ->reactive()
                                                ->visible(function (Get $get): bool {
                                                    $bodegaId = $get('../../bodega_id');

                                                    $clienteId = $get('../../cliente_id');
                                                    if (! $clienteId) {
                                                        return false;
                                                    }

                                                    $cliente = \App\Models\User::with('roles')->find($clienteId);

                                                    return $cliente?->roles->pluck('name')->intersect(['cliente_apertura', 'colaborador'])->isNotEmpty() ?? false;
                                                })

                                                ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {
                                                    $this->handleItemDiscountToggle(
                                                        $state,
                                                        $get,
                                                        $set,
                                                        'oferta_20',
                                                        function ($precioOriginal) use ($get) {
                                                            $clienteId = $get('../../cliente_id');
                                                            $cliente = \App\Models\User::find($clienteId);
                                                            if ($cliente?->hasRole('colaborador')) {
                                                                return round($precioOriginal * 0.75, 2);
                                                            }
                                                            return round($precioOriginal * 0.80, 2);
                                                        }
                                                    );
                                                }),
                                            Toggle::make('oferta')
                                                ->label('Precio Oferta')
                                                ->inline(false)
                                                ->live()
                                                ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 1])
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {
                                                    $totalPares = collect($get('../../detalles'))->sum('cantidad');

                                                    $this->handleItemDiscountToggle(
                                                        $state,
                                                        $get,
                                                        $set,
                                                        'oferta',
                                                        function ($precioOriginal) use ($get) {
                                                            // Lógica de cálculo de precio específica para este toggle
                                                            $producto = Producto::find($get('producto_id'));
                                                            $precioOferta = $producto?->precio_oferta ?? 0;
                                                            return ($precioOferta > 0) ? $precioOferta : $precioOriginal;
                                                        }
                                                    );
                                                }),
                                            Select::make('producto_id')
                                                ->label('Producto')
                                                ->relationship('producto', 'descripcion')
                                                ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, 'venta', $get('../../bodega_id'), $get('../../cliente_id')))
                                                ->allowHtml()
                                                ->reactive()
                                                ->searchable(['id', 'codigo', 'descripcion', 'marca.marca', 'genero', 'talla'])
                                                ->getSearchResultsUsing(function (string $search, Get $get): array {
                                                    return ProductoController::searchProductos($search, 'venta', $get('../../bodega_id'), $get('../../cliente_id'));
                                                })
                                                ->optionsLimit(10)
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 6])
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    $clienteId = $get('../../cliente_id');
                                                    $cantidad = $get('cantidad') ?? 1;

                                                    if (! $clienteId || ! $state) {
                                                        return;
                                                    }

                                                    $cliente = User::with('roles')->find($clienteId);
                                                    $roles = $cliente?->getRoleNames() ?? collect();
                                                    $esClienteApertura = $roles->contains('cliente_apertura');
                                                    $esColaborador = $roles->contains('colaborador');

                                                    $producto = Producto::find($state);
                                                    $precioOriginal = $producto->precio_venta;
                                                    $precioOferta = $producto->precio_oferta;

                                                    $set('precio_original', $precioOriginal);
                                                    $set('precio_oferta', $precioOferta);

                                                    $aplicarDescuento = $get('oferta_20') ?? false;
                                                    $aplicarOferta = $get('oferta') ?? false;
                                                    $precioOferta2 = $get('precio_oferta') ?? 0;
                                                    $precioFinal = $precioOriginal;

                                                    if ($esClienteApertura && $aplicarDescuento) {
                                                        $precioFinal = round($precioOriginal * 0.8, 2);
                                                        Notification::make()
                                                            ->title('Descuento aplicado')
                                                            ->body('Se ha aplicado un 20% de descuento a este producto.')
                                                            ->success()
                                                            ->send();
                                                    }

                                                    if ($esColaborador && $aplicarDescuento) {
                                                        $precioFinal = round($precioOriginal * 0.75, 2);
                                                        Notification::make()
                                                            ->title('Descuento aplicado')
                                                            ->body('Se ha aplicado un 25% de descuento a este producto.')
                                                            ->success()
                                                            ->send();
                                                    }

                                                    if ($aplicarOferta) {

                                                        if ($precioOferta2 == 0 | $precioOferta2 == null) {
                                                            $precioFinal = $precioOriginal;

                                                        } else {
                                                            $precioFinal = $precioOferta2;
                                                            Notification::make()
                                                                ->title('Descuento aplicado')
                                                                ->body('Se ha aplicado precio oferta a este producto.')
                                                                ->success()
                                                                ->send();
                                                        }

                                                    }

                                                    $set('precio', $precioFinal);
                                                    $set('subtotal', round($precioFinal * $cantidad, 2));

                                                    $productos = $get('../../detalles') ?? [];
                                                    $totalGeneral = 0;
                                                    $subtotalGeneral = 0;
                                                    foreach ($productos as $productoItem) {
                                                        $totalGeneral += (float) ($productoItem['subtotal'] ?? 0);
                                                        $subtotalGeneral += (float) ($productoItem['subtotal'] ?? 0);
                                                    }
                                                    $set('../../subtotal', round($subtotalGeneral, 2));
                                                    $set('../../total', round($totalGeneral, 2));
                                                })
                                                /* ->suffixAction(
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
                                        ) */
                                                ->required(),
                                            TextInput::make('cantidad')
                                                ->label('Cantidad')
                                                ->default(1)
                                                ->minValue(1)
                                                ->reactive()
                                                ->inputMode('decimal')
                                                ->rule('numeric')
                                                ->rules([
                                                    'required',
                                                    'numeric',
                                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                        $productoId = $get('producto_id');
                                                        $bodegaId = $get('../../bodega_id'); // subir dos niveles si está fuera del Repeater

                                                        if (! $productoId || ! is_numeric($value) || ! $bodegaId) {
                                                            return;
                                                        }

                                                        $inventario = \App\Models\Inventario::where('producto_id', $productoId)
                                                            ->where('bodega_id', $bodegaId)
                                                            ->first();

                                                        $existencia = $inventario?->existencia ?? 0;

                                                        if ($value > $existencia) {
                                                            $fail("No hay suficiente existencia en la bodega seleccionada. Existencia disponible: {$existencia}");
                                                        }
                                                    },
                                                ])
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    $precio = $get('precio') ?? 0;
                                                    $precioOriginal = $get('precio_original') ?? 0;

                                                    $clienteId = $get('../../cliente_id');
                                                    $cliente = \App\Models\User::with('roles')->find($clienteId);
                                                    $roles = $cliente?->getRoleNames() ?? collect();
                                                    $esClienteApertura = $roles->contains('cliente_apertura');
                                                    $esColaborador = $roles->contains('colaborador');

                                                    $aplicarDescuento = $get('oferta_20') ?? false;
                                                    $aplicarOferta = $get('oferta') ?? false;
                                                    $precioOferta = $get('precio_oferta') ?? 0;

                                                    $precioFinal = $precioOriginal;

                                                    if ($esClienteApertura && $aplicarDescuento) {
                                                        $precioFinal = round($precioOriginal * 0.8, 2);
                                                    }

                                                    if ($aplicarOferta) {
                                                        if ($precioOferta == 0 | $precioOferta == null) {
                                                            $precioFinal = $precioOriginal;
                                                        } else {
                                                            $precioFinal = $precioOferta;
                                                        }
                                                    }

                                                    if ($esColaborador && $aplicarDescuento) {
                                                        $precioFinal = round($precioOriginal * 0.75, 2);
                                                    }
                                                    $set('precio', $precioFinal);
                                                    $set('subtotal', round($precioFinal * $state, 2));

                                                    $productos = $get('../../detalles') ?? [];
                                                    $totalGeneral = 0;
                                                    $subtotalGeneral = 0;
                                                    foreach ($productos as $productoItem) {
                                                        $totalGeneral += (float) ($productoItem['subtotal'] ?? 0);
                                                        $subtotalGeneral += (float) ($productoItem['subtotal'] ?? 0);
                                                    }
                                                    $set('../../subtotal', round($subtotalGeneral, 2));
                                                    $set('../../total', round($totalGeneral, 2));
                                                })
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
                                                ->reactive()
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
                                            /* TextInput::make('comision')
                                        ->label('Comisión (%)')
                                        ->readOnly()
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]), */
                                            Hidden::make('escala_id'),
                                            /* Hidden::make('precio_comp'), */
                                            /* Hidden::make('ganancia'), */
                                            TextInput::make('subtotal')
                                                ->label('SubTotal')
                                                ->prefix('Q')
                                                ->default(0)
                                                ->reactive()
                                                ->readOnly()
                                                ->columnSpan(['default' => 2,  'md' => 3, 'lg' => 4, 'xl' => 2])
                                                ->afterStateUpdated(fn (Set $set, Get $get) => $set('subtotal', $get('cantidad') * $get('precio'))
                                                ),
                                        ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Producto')
                                        ->live()
                                        ->reactive()
                                        ->visible(fn (Get $get): bool => ! empty($get('bodega_id')))
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $productos = $get('detalles') ?? [];
                                            $totalGeneral = 0;
                                            $subtotalGeneral = 0;

                                            foreach ($productos as $productoItem) {
                                                $cantidad = (float) ($productoItem['cantidad'] ?? 0);
                                                $precio = (float) ($productoItem['precio'] ?? 0);
                                                $totalGeneral += ($cantidad * $precio);
                                                $subtotalGeneral += ($cantidad * $precio);
                                            }

                                            $set('../../subtotal', round($subtotalGeneral, 2));
                                            $set('../../total', round($totalGeneral, 2));
                                        }),

                                ])]),
                    Wizard\Step::make('Pagos')
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
                                    TextInput::make('no_documento')
                                        ->label('No. Documento o Autorización')
                                        ->columnSpan(['sm' => 1, 'md' => 2])
                                        ->required(fn (Get $get) => in_array(
                                            optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago,
                                            ['TRANSFERENCIA', 'DEPOSITO']
                                        ))
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
                                        ]),

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
                                    /* FileUpload::make('imagen')
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
                                        ->optimize('webp'), */
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

    protected function beforeCreate(): void
    {
        try {
            $totalVenta = $this->data['total'] ?? 0;
            $totalPagos = collect($this->data['pagos'] ?? [])->sum('monto');

            $bodegaId = $this->data['bodega_id'] ?? null;
            $cierreAbierto = Cierre::where('bodega_id', $bodegaId)
                ->whereNull('cierre')
                ->exists();

            if (! $cierreAbierto) {
                throw ValidationException::withMessages([
                    'bodega_id' => 'No hay un cierre abierto para la bodega seleccionada.',
                ]);
            }

            if (round($totalVenta, 2) != round($totalPagos, 2)) {
                throw ValidationException::withMessages([
                    'pagos' => 'El total de los pagos no coincide con el total de la venta.',
                ]);
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['asesor_id'] = auth()->user()->id;
        $data['estado'] = 'creada';

        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            DB::transaction(function () {
                if ($this->record->tipo_pago_id == 2) {
                    UserController::sumarSaldo(User::find($this->data['cliente_id']), $this->data['total']);
                }
                VentaController::facturar($this->record);
                Notification::make()
                    ->title('Venta registrada correctamente')
                    ->success()
                    ->color('success')
                    ->send();
            });
        } catch (\Exception $e) {
            $this->record->detalles()->delete();
            $this->record->pagos()->delete();
            $this->record->factura()->delete();
            $this->record->delete();
            Notification::make()
                ->danger()
                ->color('danger')
                ->title('Error al registrar la venta')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            $this->halt();
        }
    }
}
