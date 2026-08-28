<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use App\Filament\Traits\ManageDiscountLogic;
use App\Filament\Ventas\Resources\VentaResource;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use App\Models\Banco;
use App\Models\Cierre;
use App\Models\Departamento;
use App\Models\Factura;
use App\Models\Municipio;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\TipoPago;
use App\Models\User;
use App\Models\ValeRegalo;
use Closure;
use Filament\Forms\Components\DatePicker;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    use ManageDiscountLogic;

    protected $subtotalOriginal;

    protected function calcularPrecioDetalle(int $productoId, string $tipoPrecio, int $cantidad, bool $descuento5): float
    {
        $producto = Producto::find($productoId);
        if (!$producto) {
            return 0.0;
        }

        return (float) $producto->precio_venta;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])
                    ->schema([
                        Select::make('bodega_id')
                            ->relationship(
                                'bodega',
                                'bodega',
                                fn (Builder $query) => $query
                                    ->whereHas('user', fn ($q) => $q->where('user_id', Auth::user()?->id)
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
                            ->required()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                                    if ($value) {
                                        $userId = Auth::user()?->id;
                                        $cierreAbierto = Cierre::where('bodega_id', $value)
                                            ->where('user_id', $userId)
                                            ->whereNull('cierre')
                                            ->exists();

                                        if (! $cierreAbierto) {
                                            $fail('No tienes un cierre abierto en esta bodega. Debes aperturar un cierre antes de realizar ventas.');
                                        }
                                    }
                                },
                            ]),
                        Select::make('asesor_id')
                            ->label('Vendedor')
                            ->relationship(
                                'asesor',
                                'name',
                                fn (Builder $query) => $query->role(['telemarketing'])
                            )
                            ->options(function () {
                                $currentUser = Auth::user();
                                $options = [];

                                // Si el usuario actual es vendedor o telemarketing, lo agregamos primero
                                if ($currentUser && $currentUser->hasAnyRole(['vendedor'])) {
                                    $options[$currentUser->id] = $currentUser->name . ($currentUser->apellido ? " {$currentUser->apellido}" : "") . ' (Usuario actual)';
                                }

                                // Agregamos otros vendedores y telemarketing
                                $query = User::role(['telemarketing']);
                                if ($currentUser) {
                                    $query->where('id', '!=', $currentUser->id);
                                }
                                $otherVendedores = $query->get();

                                foreach ($otherVendedores as $vendedor) {
                                    $options[$vendedor->id] = $vendedor->name . ($vendedor->apellido ? " {$vendedor->apellido}" : "");
                                }

                                return $options;
                            })
                            ->default(function () {
                                $currentUser = Auth::user();
                                // Si el usuario actual es vendedor o telemarketing, lo seleccionamos por defecto
                                if ($currentUser && $currentUser->hasAnyRole(['vendedor'])) {
                                    return $currentUser->id;
                                }

                                return null;
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('tipo_envio')
                            ->label('Tipo de envío')
                            ->options(['guatex' => 'GUATEX', 'propio' => 'PROPIO'])
                            ->preload()
                            ->live()
                            ->searchable()
                            ->required()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state !== 'guatex') {
                                    return;
                                }

                                $pagos = $get('pagos') ?? [];

                                foreach ($pagos as $key => $pago) {
                                    $tipo = TipoPago::find($pago['tipo_pago_id'] ?? null)?->tipo_pago;
                                    if (! in_array($tipo, TipoPago::FORMAS_PAGO_GUATEX)) {
                                        $pagos[$key]['tipo_pago_id'] = null;
                                    }
                                }

                                $set('pagos', $pagos);
                            }),
                    ]),
                    
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
                                        ->getOptionLabelFromRecordUsing(
                                            fn ($record) => "{$record->id} - {$record->nit} - {$record->razon_social} - {$record->name}" . ($record->apellido ? " {$record->apellido}" : "")
                                        )
                                        ->optionsLimit(20)
                                        ->required()
                                        ->live()
                                        ->reactive()
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            $set('detalles', []);

                                            if ($state) {
                                                $cliente = User::find($state);
                                                if ($cliente) {
                                                    $nit = strtoupper(trim($cliente->nit ?? ''));
                                                    $total = (float) ($get('total') ?? 0);

                                                    // El cliente tiene NIT real: no puede facturarse como CF
                                                    if ($nit !== '' && $nit !== 'CF') {
                                                        $set('facturar_cf', false);
                                                    }

                                                    // Validar si el cliente tiene NIT "CF" y el total actual excede 2500
                                                    if ($nit === 'CF' && $total > Factura::CF) {
                                                        Notification::make()
                                                            ->title('Venta excede el límite')
                                                            ->body('Las ventas para clientes con NIT "CF" no pueden ser mayores a Q'.Factura::CF.'.')
                                                            ->warning()
                                                            ->send();
                                                    }
                                                }
                                            }
                                        })
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
                                                ->rules([
                                                    'regex:/^[^a-z]+$/',
                                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                                                        // Solo validar unique si el NIT no es CF
                                                        if (strtoupper(trim($value)) !== 'CF') {
                                                            if (User::where('nit', $value)->exists()) {
                                                                 $fail('El campo NIT ya ha sido registrado.');
                                                            }
                                                        }
                                                    },
                                                ])
                                                ->validationMessages([
                                                    'regex' => 'El NIT no puede contener letras minúsculas.',
                                                ])
                                                ->afterStateUpdated(function (Set $set, $state) {
                                                    $nit = UserController::nit($state);
                                                    if ($nit !== null) {
                                                        $set('razon_social', $nit);
                                                    }
                                                }),
                                            TextInput::make('razon_social')
                                                ->required()
                                                ->readOnly()
                                                ->default('CF')
                                                ->label('Razón Social')
                                                ->rules(['regex:/^[^a-z]+$/'])
                                                ->validationMessages([
                                                    'regex' => 'No se permiten letras minúsculas.',
                                                ]),
                                            TextInput::make('name')
                                                ->required()
                                                ->unique(table: User::class)
                                                ->label('Nombre/Nombre Comercial')
                                                ->minLength(5)
                                                ->rules(['regex:/^[^a-z]+$/', 'regex:/[A-Z]/'])
                                                ->validationMessages([
                                                    'regex' => 'El nombre debe contener al menos una letra y estar en MAYÚSCULAS.',
                                                    'min' => 'El nombre debe tener al menos 5 caracteres.',
                                                ]),
                                            TextInput::make('apellido')
                                                ->required()
                                                ->label('Apellido')
                                                ->rules(['regex:/^[^a-z]+$/', 'regex:/[A-Z]/'])
                                                ->validationMessages([
                                                    'regex' => 'El apellido debe contener al menos una letra y estar en MAYÚSCULAS.',
                                                ]),
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
                                                        ->maxLength(255)
                                                        ->rules(['regex:/^[^a-z]+$/'])
                                                        ->validationMessages([
                                                            'regex' => 'No se permiten letras minúsculas.',
                                                        ]),
                                                    TextInput::make('referencia')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->rules(['regex:/^[^a-z]+$/'])
                                                        ->validationMessages([
                                                            'regex' => 'No se permiten letras minúsculas.',
                                                        ]),
                                                    TextInput::make('zona')
                                                        ->label('Zona')
                                                        ->inputMode('decimal')
                                                        ->rule('numeric')
                                                        ->minValue(0),
                                                ])->columnSpanFull()->columns(3)->defaultItems(0),
                                        ])
                                        ->createOptionUsing(function (array $data): int {
                                            $user = User::create($data);
                                            $user->assignRole('cliente'); // Asigna el rol automáticamente

                                            return $user->id; // Devuelve el ID para que se seleccione en el campo
                                        })
                                        ->searchable([
                                            'id',
                                            'nit',
                                            'name',
                                            'apellido',
                                            'razon_social',
                                            'telefono'
                                        ]),
                                    Toggle::make('facturar_cf')
                                        ->inline(false)
                                        ->live()
                                        ->disabled(function (Get $get) {
                                            if ($get('total') >= Factura::CF) {
                                                return true;
                                            }

                                            $cliente = $get('cliente_id') ? User::find($get('cliente_id')) : null;
                                            $nit = strtoupper(trim($cliente->nit ?? ''));

                                            return $nit !== '' && $nit !== 'CF';
                                        })
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            if ($get('facturar_cf')) {
                                                // Validar que el total no exceda 2500 cuando se activa facturar_cf
                                                $total = (float) ($get('total') ?? 0);
                                                if ($total > Factura::CF) {
                                                    Notification::make()
                                                        ->title('Venta excede el límite')
                                                        ->body('Las ventas con "Facturar CF" activo no pueden ser mayores a Q'.Factura::CF.'.')
                                                        ->danger()
                                                        ->send();
                                                    $set('facturar_cf', false);
                                                }
                                            }
                                        })
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                $clienteId = $get('cliente_id');
                                                if ($clienteId) {
                                                    $cliente = User::find($clienteId);
                                                    $razonSocial = strtoupper(trim($cliente->razon_social ?? ''));
                                                    $nit = strtoupper(trim($cliente->nit ?? ''));

                                                    if ($razonSocial === 'CF' && ! $value) {
                                                        $fail('El cliente tiene razón social CF, debe activar esta opción.');
                                                    }

                                                    if ($value && $nit !== '' && $nit !== 'CF') {
                                                        $fail('El cliente tiene un NIT registrado, no puede facturarse como Consumidor Final (CF).');
                                                    }
                                                }

                                                // Validar que el total no exceda 2500 cuando se activa facturar_cf
                                                if ($value) {
                                                    $total = (float) ($get('total') ?? 0);
                                                    if ($total > Factura::CF) {
                                                        $fail('Las ventas con "Facturar CF" activo no pueden ser mayores a Q'.Factura::CF.'.');
                                                    }
                                                }
                                            },
                                        ])
                                        ->label('Facturar CF')
                                        ->columnSpan(2),
                                    Repeater::make('detalles')
                                        ->label('')
                                        ->relationship()
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->columns(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 6])
                                        ->grid([
                                            'default' => 1,
                                            'md' => 2,
                                            'xl' => 2,
                                        ])
                                        ->schema([
                                            Hidden::make('uuid')
                                                ->default(fn () => (string) Str::uuid())
                                                ->dehydrated(false),
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
                                                    $cantidad = (int) ($get('cantidad') ?? 1);

                                                    if (! $state) {
                                                        return;
                                                    }

                                                    $producto = Producto::find($state);
                                                    if (!$producto) {
                                                        return;
                                                    }

                                                    $precio = (float) $producto->precio_venta;

                                                    $set('precio', $precio);
                                                    $set('precio_base', $precio);
                                                    $set('subtotal', round($precio * $cantidad, 2));
                                                    $this->updateOrderTotals($get, $set);
                                                })
                                                ->required(),
                                            Hidden::make('tipo_precio')
                                                ->default('normal')
                                                ->dehydrated(false),
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
                                                    $productoId = $get('producto_id');
                                                    if (! $productoId) {
                                                        return;
                                                    }

                                                    $precioFinal = $this->calcularPrecioDetalle((int) $productoId, 'normal', (int) $state, false);

                                                    $set('precio', $precioFinal);
                                                    $set('subtotal', round($precioFinal * $state, 2));
                                                    $this->updateOrderTotals($get, $set);
                                                })
                                                ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                                ->required(),
                                            TextInput::make('precio')
                                                ->label('Precio')
                                                ->default(0)
                                                ->readOnly()
                                                ->reactive()
                                                ->required()
                                                ->prefix('Q')
                                                ->inputMode('decimal')
                                                ->rule('numeric')
                                                ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                            Hidden::make('precio_original')
                                            ->dehydrated(false),
                                            Hidden::make('precio_base')
                                                ->dehydrated(false),
                                            Hidden::make('escala_id'),
                                            TextInput::make('subtotal')
                                                ->label('SubTotal')
                                                ->prefix('Q')
                                                ->default(0)
                                                ->reactive()
                                                ->readOnly()
                                                ->columnSpan(['default' => 2,  'md' => 3, 'lg' => 4, 'xl' => 2])
                                                ->afterStateUpdated(fn (Set $set, Get $get) => $set('subtotal', (float) $get('cantidad') * (float) $get('precio'))
                                                ),
                                        ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Producto')
                                        ->live()
                                        ->reactive()
                                        ->visible(fn (Get $get): bool => ! empty($get('bodega_id')))
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $this->updateOrderTotals($get, $set);

                                            $productos = $get('detalles') ?? [];
                                            $totalGeneral = collect($productos)->sum(fn($p) => (float) ($p['cantidad'] ?? 0) * (float) ($p['precio'] ?? 0));

                                            // Validar límite de 2500 cuando facturar_cf está activo o el NIT es CF
                                            $facturarCf = $get('facturar_cf') ?? false;
                                            $clienteId = $get('cliente_id');

                                            if ($clienteId && ($facturarCf || $totalGeneral > Factura::CF)) {
                                                $cliente = User::find($clienteId);
                                                if ($cliente) {
                                                    $nit = strtoupper(trim($cliente->nit ?? ''));
                                                    if (($facturarCf || $nit === 'CF') && $totalGeneral > Factura::CF) {
                                                        Notification::make()
                                                            ->title('Venta excede el límite')
                                                            ->body('Las ventas no pueden ser mayores a Q'.Factura::CF.' cuando "Facturar CF" está activo o el NIT del cliente es "CF".')
                                                            ->warning()
                                                            ->send();
                                                    }
                                                }
                                            }
                                        }),

                                ])]),
                    Wizard\Step::make('Pagos')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 10,
                            ])
                                ->schema([
                                ]),

                            /* Select::make('condicion_pago')
                                ->label('Condición de la venta')
                                ->options([
                                    'normal' => 'Normal / Crédito / Mixto',
                                ])
                                ->default('normal')
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {

                                    $detalles = $get('detalles') ?? [];

                                    $nuevoSubtotal = 0;

                                    foreach ($detalles as $index => $item) {
                                        $precioBase = (float) ($item['precio_base'] ?? $item['precio'] ?? 0);
                                        $cantidad = (int) ($item['cantidad'] ?? 1);

                                        if ($state === 'contado') {
                                            $precioFinal = round($precioBase * 0.95, 2);
                                        } else {
                                            $precioFinal = $precioBase;
                                        }

                                        $set("detalles.$index.precio", $precioFinal);
                                        $set("detalles.$index.subtotal", round($precioFinal * $cantidad, 2));

                                        $nuevoSubtotal += $precioFinal * $cantidad;
                                    }

                                    $set('subtotal', round($nuevoSubtotal, 2));
                                    $set('total', round($nuevoSubtotal, 2));

                                    $set('pagos', []);
                                }), */
                            Hidden::make('descuento_efectivo_5')
                                ->dehydrated(false)
                                ->reactive(),
                            Repeater::make('pagos')
                                ->label('Pagos')
                                ->required()
                                ->relationship()
                                ->minItems(1)
                                ->defaultItems(1)
                                ->columns(6)
                                ->live()
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Forma de Pago')
                                        ->required()
                                        ->live()
                                        ->searchable()
                                        ->preload()
                                        ->options(function (Get $get) {

                                            $condicion = $get('../../condicion_pago');

                                            if ($get('../../tipo_envio') === 'guatex') {
                                                return TipoPago::whereIn('tipo_pago', TipoPago::FORMAS_PAGO_GUATEX)
                                                    ->pluck('tipo_pago', 'id')
                                                    ->toArray();
                                            }

                                            if ($condicion === 'contado') {
                                                return TipoPago::whereIn('tipo_pago', ['CONTADO'])
                                                    ->pluck('tipo_pago', 'id')
                                                    ->toArray();
                                            }

                                            return TipoPago::whereIn('tipo_pago', TipoPago::FORMAS_PAGO_VENTA)
                                                ->pluck('tipo_pago', 'id')
                                                ->toArray();
                                        })
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {

                                            if ($get('../../tipo_envio') === 'guatex') {
                                                $tipo = TipoPago::find($state)?->tipo_pago;
                                                if (! in_array($tipo, TipoPago::FORMAS_PAGO_GUATEX)) {
                                                    $set('tipo_pago_id', null);
                                                }
                                            }

                                            if ($get('../../condicion_pago') === 'contado') {
                                                $tipo = TipoPago::find($state)?->tipo_pago;
                                                if (! in_array($tipo, ['CONTADO'])) {
                                                    $set('tipo_pago_id', null);
                                                }
                                            }

                                            if (! in_array($state, array_keys(\App\Models\TipoPago::FORMAS_PAGO_TARJETA))) {
                                                $set('nombre_tarjeta', null);
                                                $set('ult_dgt', null);
                                            }

                                            $tipo = optional(TipoPago::find($state))->tipo_pago;
                                            if ($tipo !== 'VALE DE REGALO') {
                                                $set('vale_regalo_id', null);
                                            }
                                        }),
                                    Select::make('vale_regalo_id')
                                        ->label('Vale de Regalo')
                                        ->columnSpan(['sm' => 1, 'md' => 2])
                                        ->visible(fn (Get $get) => optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago === 'VALE DE REGALO')
                                        ->required(fn (Get $get) => optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago === 'VALE DE REGALO')
                                        ->options(function () {
                                            return ValeRegalo::where('estado', 'disponible')
                                                ->get()
                                                ->mapWithKeys(fn ($vale) => [
                                                    $vale->id => "No. {$vale->correlativo} - Q{$vale->monto} (De: {$vale->de} / Para: {$vale->para})"
                                                ]);
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            if ($state) {
                                                $vale = ValeRegalo::find($state);
                                                if ($vale) {
                                                    $set('monto', $vale->monto);
                                                    $set('total', $vale->monto);
                                                    $set('no_documento', $vale->correlativo);
                                                }
                                            }
                                        }),
                                    TextInput::make('nombre_tarjeta')
                                        ->label('Nombre de la Tarjeta')
                                        ->visible(fn (Get $get) => in_array(
                                            $get('tipo_pago_id'),
                                            array_keys(\App\Models\TipoPago::FORMAS_PAGO_TARJETA)
                                        )),
                                    TextInput::make('ult_dgt')
                                        ->label('Ultimos 4 digitos de la tarjeta')
                                        ->visible(fn (Get $get) => in_array(
                                            $get('tipo_pago_id'),
                                            array_keys(\App\Models\TipoPago::FORMAS_PAGO_TARJETA)
                                        ))
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->minValue(1)
                                        ->rule('digits:4'),
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
                                        ->required(fn (Get $get) => ! in_array(optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago, ['CONTADO', 'PAGO CONTRA ENTREGA', 'VALE DE REGALO']))
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                                                // Solo validar si el valor no está vacío
                                                if (empty($value)) {
                                                    return;
                                                }

                                                // Validar que no_documento sea único en toda la tabla de pagos
                                                if (Pago::where('no_documento', $value)->exists()) {
                                                    $fail('El número de documento ya existe en los pagos.');
                                                }
                                            },
                                        ]),
                                    Select::make('banco_id')
                                        ->label('Banco')
                                        ->columnSpan(['sm' => 1, 'md' => 2])
                                        ->required(fn (Get $get) => ! in_array(optional(TipoPago::find($get('tipo_pago_id')))->tipo_pago, ['CONTADO', 'PAGO CONTRA ENTREGA', 'VALE DE REGALO']))
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
                            ->label('Total')
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $total = (float) ($value ?? 0);
                                    $facturarCf = $get('facturar_cf') ?? false;
                                    $clienteId = $get('cliente_id');

                                    // Verificar si facturar_cf está activo
                                    if ($facturarCf && $total > Factura::CF) {
                                        $fail('Las ventas con "Facturar CF" activo no pueden ser mayores a Q'.Factura::CF.'.');

                                        return;
                                    }

                                    // Verificar si el NIT del cliente es CF o cf
                                    if ($clienteId) {
                                        $cliente = User::find($clienteId);
                                        if ($cliente) {
                                            $nit = strtoupper(trim($cliente->nit ?? ''));
                                            if (($nit === 'CF') && $total > Factura::CF) {
                                                $fail('Las ventas para clientes con NIT "CF" no pueden ser mayores a Q'.Factura::CF.'.');

                                                return;
                                            }
                                        }
                                    }
                                },
                            ]),
                    ]),

            ]);
    }

    protected function beforeCreate(): void
    {
        try {
            $totalVenta = $this->data['total'] ?? 0;
            $totalPagos = collect($this->data['pagos'] ?? [])->sum('monto');

            $bodegaId = $this->data['bodega_id'] ?? null;
            $userId = Auth::user()?->id;

            // Verificar que el usuario actual tenga un cierre abierto en la bodega seleccionada
            $cierreAbierto = Cierre::where('bodega_id', $bodegaId)
                ->where('user_id', $userId)
                ->whereNull('cierre')
                ->exists();

            if (! $cierreAbierto) {
                throw ValidationException::withMessages([
                    'bodega_id' => 'No tienes un cierre abierto en la bodega seleccionada. Debes aperturar un cierre antes de realizar ventas.',
                ]);
            }

            if (round($totalVenta, 2) != round($totalPagos, 2)) {
                throw ValidationException::withMessages([
                    'pagos' => 'El total de los pagos no coincide con el total de la venta.',
                ]);
            }

            // Validar que si la razón social del cliente es CF, el campo facturar_cf debe ser true
            $clienteId = $this->data['cliente_id'] ?? null;
            $facturarCf = $this->data['facturar_cf'] ?? false;

            if ($clienteId) {
                $cliente = User::find($clienteId);
                $razonSocial = strtoupper(trim($cliente->razon_social ?? ''));

                if ($razonSocial === 'CF' && ! $facturarCf) {
                    throw ValidationException::withMessages([
                        'facturar_cf' => 'El cliente tiene razón social CF, debe activar la opción "Facturar CF".',
                    ]);
                }
            }

            // Validar que las ventas no pueden ser mayores a 2500 cuando facturar_cf está activo o el NIT es CF/cf
            if ($clienteId) {
                $cliente = User::find($clienteId);
                $nit = strtoupper(trim($cliente->nit ?? ''));

                if (($facturarCf || $nit === 'CF') && $totalVenta > Factura::CF) {
                    throw ValidationException::withMessages([
                        'total' => 'Las ventas no pueden ser mayores a Q'.Factura::CF.' cuando "Facturar CF" está activo o el NIT del cliente es "CF".',
                    ]);
                }

                // Un cliente con NIT real no puede facturarse como Consumidor Final (CF)
                if ($facturarCf && $nit !== '' && $nit !== 'CF') {
                    throw ValidationException::withMessages([
                        'facturar_cf' => 'El cliente tiene un NIT registrado, no puede facturarse como Consumidor Final (CF).',
                    ]);
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si no se seleccionó un asesor, usar el usuario actual como fallback
        if (empty($data['asesor_id'])) {
            $data['asesor_id'] = Auth::user()?->id;
        }
        $data['estado'] = 'creada';

        $cliente = ! empty($data['cliente_id']) ? User::with('roles')->find($data['cliente_id']) : null;
        $data['requiere_evidencia_oferta20'] = $cliente?->getRoleNames()->contains('cliente_apertura') ?? false;

        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            DB::transaction(function () {
                // dd($this->data['detalles']);
                foreach ($this->record->detalles as $detalle) {
                    $detalleData = collect($this->data['detalles'])->first(fn ($d) => ($d['producto_id'] ?? null) == $detalle->producto_id && ($d['cantidad'] ?? null) == $detalle->cantidad);
                    if ($detalleData && ($detalleData['oferta_cliente_20'] ?? false)) {
                        $detalle->oferta_cliente_20 = true;
                        $detalle->save();
                    }
                }

                $tipoPagoPrincipal = $this->record->pagos()->first()?->tipo_pago_id;
                $requiereValidacionPago = in_array($tipoPagoPrincipal, [5, 9]);

                if ($requiereValidacionPago) {
                    $this->record->requiere_validacion_pago = true;
                }

                if ($requiereValidacionPago || $this->record->requiere_evidencia_oferta20) {
                    $this->record->estado = 'validacion_pago';
                    $this->record->save();

                    Notification::make()
                        ->title('Venta registrada pendiente de validación')
                        ->body('Motivo(s): '.implode(', ', $this->record->motivosPendientes()).'. Debe ser validada por un administrador antes de generar factura.')
                        ->warning()
                        ->send();

                    return;
                }

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
