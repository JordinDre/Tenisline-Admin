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

        $precioBase = (float) $producto->precio_venta;

        switch ($tipoPrecio) {
            case 'oferta':
                $precioBase = ($producto->precio_oferta > 0) ? (float) $producto->precio_oferta : (float) $producto->precio_venta;
                break;
            case 'liquidacion':
                $precioBase = $this->calcularPrecioLiquidacion($producto);
                break;
            case 'descuento':
                if ($producto->precio_descuento > 0) {
                    $precioBase = round($producto->precio_venta * (1 - ($producto->precio_descuento / 100)), 2);
                }
                break;
            case 'segundo_par':
                $precioBase = $this->calcularPrecioSegundoPar($producto);
                break;
            case 'apertura_20':
                $precioBase = round($producto->precio_venta * 0.80, 2);
                break;
            default:
                $precioBase = (float) $producto->precio_venta;
                break;
        }

        $precioFinal = $precioBase;

        if ($descuento5) {
            $descuento5Porciento = round($producto->precio_venta * 0.05, 2);
            $precioFinal = round($precioBase - $descuento5Porciento, 2);
        }

        return (float) $precioFinal;
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
                            ->required(),
                        Select::make('condicion_pago')
                                ->label('Condición de la venta')
                                ->options([
                                    'normal' => 'Normal / Crédito / Mixto',
                                ])
                                ->default('normal')
                                ->live()
                                ->dehydrated(false)
                                /* ->afterStateUpdated(function ($state, Set $set, Get $get) {

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
                                }) */,
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

                                            // Validar si el cliente tiene NIT "CF" y el total actual excede 2500
                                            if ($state) {
                                                $cliente = User::find($state);
                                                if ($cliente) {
                                                    $nit = strtoupper(trim($cliente->nit ?? ''));
                                                    $total = (float) ($get('total') ?? 0);
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
                                                    $set('razon_social', $nit);
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
                                        ->disabled(fn (Get $get) => $get('total') >= Factura::CF)
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            if (! $get('facturar_cf')) {
                                                $set('comp', false);
                                            } else {
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

                                                    if ($razonSocial === 'CF' && ! $value) {
                                                        $fail('El cliente tiene razón social CF, debe activar esta opción.');
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
                                        Toggle::make('comp')
                                            ->inline(false)
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                            })
                                            ->rules([
                                            ])
                                            ->label('COMP')
                                            ->columnSpan(1),
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
                                            Toggle::make('5%')
                                                ->inline(false)
                                                ->live()
                                                ->visible(fn (Get $get) => $get('../../condicion_pago') === 'contado')
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    $productoId = $get('producto_id');
                                                    if (!$productoId) {
                                                        return;
                                                    }

                                                    $tipoPrecio = $get('tipo_precio') ?? 'normal';
                                                    $cantidad = (int) ($get('cantidad') ?? 1);

                                                    $precioBase = $this->calcularPrecioDetalle((int) $productoId, $tipoPrecio, $cantidad, false);
                                                    $precioFinal = $this->calcularPrecioDetalle((int) $productoId, $tipoPrecio, $cantidad, (bool) $state);

                                                    $set('precio', $precioFinal);
                                                    $set('precio_base', $precioBase);
                                                    $set('subtotal', round($precioFinal * $cantidad, 2));
                                                    $this->updateOrderTotals($get, $set);
                                                })
                                                ->label('5% extra')
                                                ->dehydrated(false)
                                                ->columnSpan(1),
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
                                                    $clienteId = $get('../../cliente_id');
                                                    $cantidad = (int) ($get('cantidad') ?? 1);

                                                    if (! $clienteId || ! $state) {
                                                        return;
                                                    }

                                                    $producto = Producto::find($state);
                                                    if (!$producto) {
                                                        return;
                                                    }
                                                    $precioOriginal = $producto->precio_venta;
                                                    $precioOferta = $producto->precio_oferta;

                                                    $set('precio_original', $precioOriginal);
                                                    $set('precio_oferta', $precioOferta);
                                                    $set('tipo_precio', 'normal');

                                                    $descuento5 = (bool) $get('5%');
                                                    $precioBase = $this->calcularPrecioDetalle((int) $state, 'normal', $cantidad, false);
                                                    $precioFinal = $this->calcularPrecioDetalle((int) $state, 'normal', $cantidad, $descuento5);

                                                    $set('precio', $precioFinal);
                                                    $set('precio_base', $precioBase);
                                                    $set('subtotal', round($precioFinal * $cantidad, 2));
                                                    $this->updateOrderTotals($get, $set);
                                                })
                                                ->required(),
                                            Select::make('tipo_precio')
                                                ->label('Tipo de precio')
                                                ->options(function (Get $get) {
                                                    $productoId = $get('producto_id');
                                                    if (! $productoId) {
                                                        return [];
                                                    }

                                                    $producto = \App\Models\Producto::find($productoId);
                                                    if (! $producto) {
                                                        return [];
                                                    }

                                                    $precios = [
                                                        'normal' => 'Precio Normal (Q'.$producto->precio_venta.')',
                                                    ];

                                                    $detalles = $get('../../detalles') ?? [];
                                                    $currentUuid = $get('uuid') ?? null;

                                                    $haySegundoPar = collect($detalles)->contains(function ($item) use ($currentUuid) {
                                                        return (($item['uuid'] ?? null) !== $currentUuid)
                                                            && ($item['tipo_precio'] ?? null) === 'segundo_par';
                                                    });

                                                    $hayOtrasOfertas = collect($detalles)->contains(function ($item) use ($currentUuid) {
                                                        return (($item['uuid'] ?? null) !== $currentUuid)
                                                            && in_array($item['tipo_precio'] ?? null, ['oferta', 'liquidacion', 'descuento', 'apertura_20'], true);
                                                    });

                                                    $esMarchamoRojo = strtolower($producto->marchamo ?? '') === 'rojo';

                                                    if ($producto->precio_liquidacion > 0 && (!$haySegundoPar || $esMarchamoRojo)) {
                                                        $precioCalculado = self::calcularPrecioLiquidacion($producto);
                                                        $precios['liquidacion'] = 'Liquidación ('.$producto->precio_liquidacion.'% descuento → Q'.$precioCalculado.')';
                                                    }

                                                    if ($producto->precio_oferta > 0 && !$haySegundoPar) {
                                                        $precios['oferta'] = 'Precio Oferta (Q'.$producto->precio_oferta.')';
                                                    }

                                                    $tieneOtrasOfertasNoMarchamoRojo = false;
                                                    if ($hayOtrasOfertas) {
                                                        $otrosDetalles = collect($detalles)->filter(fn($item) => ($item['uuid'] ?? null) !== $currentUuid);
                                                        $productoIdsOtros = $otrosDetalles->pluck('producto_id')->filter()->unique();
                                                        $productosOtros = \App\Models\Producto::whereIn('id', $productoIdsOtros)->get()->keyBy('id');

                                                        foreach ($otrosDetalles as $item) {
                                                            if (in_array($item['tipo_precio'] ?? null, ['oferta', 'liquidacion', 'descuento', 'apertura_20'], true)) {
                                                                $pOtro = $productosOtros->get($item['producto_id'] ?? null);
                                                                $esMRLiq = $item['tipo_precio'] === 'liquidacion' && $pOtro && strtolower($pOtro->marchamo ?? '') === 'rojo';
                                                                if (!$esMRLiq) {
                                                                    $tieneOtrasOfertasNoMarchamoRojo = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if ($producto->precio_segundo_par > 0 && $producto->precio_venta > 0 && !$tieneOtrasOfertasNoMarchamoRojo) {
                                                        $precioCalculado = self::calcularPrecioSegundoPar($producto);
                                                        $precios['segundo_par'] = 'Segundo Par ('.$producto->precio_segundo_par.'% descuento → Q'.$precioCalculado.')';
                                                    }

                                                    if ($producto->precio_descuento > 0 && !$haySegundoPar) {
                                                        $precios['descuento'] = 'Precio con Descuento '.$producto->precio_descuento.'%';
                                                    }

                                                    $clienteId = $get('../../cliente_id');
                                                    if ($clienteId && !$haySegundoPar) {
                                                        $roles = \App\Models\User::with('roles')
                                                            ->find($clienteId)?->getRoleNames() ?? collect();

                                                        if ($roles->contains('cliente_apertura')) {
                                                            $precios['apertura_20'] = 'Cliente Apertura (20% descuento)';
                                                        }
                                                    }

                                                    return $precios;
                                                })
                                                ->reactive()
                                                ->dehydrated(false)
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    $productoId = $get('producto_id');
                                                    if (! $productoId) {
                                                        return;
                                                    }

                                                    $producto = \App\Models\Producto::find($productoId);
                                                    if (! $producto) {
                                                        return;
                                                    }

                                                    $detalles = $get('../../detalles') ?? [];
                                                    $currentUuid = $get('uuid') ?? null;
                                                    $cantidadActual = (int) ($get('cantidad') ?? 1);

                                                    // total pares en toda la orden (suma de cantidades)
                                                    $totalPares = collect($detalles)->sum(fn ($i) => (int) ($i['cantidad'] ?? 0));

                                                    if ($state === 'segundo_par') {
                                                        $detalles = $this->getDetallesArray($get);
                                                        $currentUuid = $get('uuid') ?? null;
                                                        $cantidadActual = (int) ($get('cantidad') ?? 1);

                                                        if (! $this->hayAlgunoConPorcentajeSegundoPar($detalles)) {
                                                            Notification::make()
                                                                ->title('Oferta no disponible')
                                                                ->body('Ningún producto tiene configurado porcentaje de "Segundo Par".')
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $this->restoreOriginalPrice($get, $set);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        $totalPares = $this->totalPares($detalles);
                                                        if ($totalPares < 2) {
                                                            Notification::make()
                                                                ->title('Descuento no aplicable')
                                                                ->body('El precio de "Segundo Par" requiere al menos 2 pares en total.')
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $this->restoreOriginalPrice($get, $set);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        $hayOtrasOfertasEnOtros = false;
                                                        $otrosDetalles = collect($detalles)->filter(fn($item) => ($item['uuid'] ?? null) !== $currentUuid);
                                                        $productoIdsOtros = $otrosDetalles->pluck('producto_id')->filter()->unique();
                                                        $productosOtros = \App\Models\Producto::whereIn('id', $productoIdsOtros)->get()->keyBy('id');

                                                        foreach ($otrosDetalles as $item) {
                                                            if (in_array($item['tipo_precio'] ?? null, ['oferta', 'liquidacion', 'descuento', 'apertura_20'], true)) {
                                                                $pOtro = $productosOtros->get($item['producto_id'] ?? null);
                                                                $esMRLiq = $item['tipo_precio'] === 'liquidacion' && $pOtro && strtolower($pOtro->marchamo ?? '') === 'rojo';
                                                                if (!$esMRLiq) {
                                                                    $hayOtrasOfertasEnOtros = true;
                                                                    break;
                                                                }
                                                            }
                                                        }

                                                        if ($hayOtrasOfertasEnOtros) {
                                                            Notification::make()
                                                                ->title('Conflicto de promociones')
                                                                ->body('No puedes aplicar el descuento de "Segundo Par" si ya hay otros productos con descuento o promoción.')
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $this->restoreOriginalPrice($get, $set);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        $permitidos = $this->paresPermitidos($totalPares);
                                                        $yaConSegundoPar = $this->paresConSegundoParExcluyendo($detalles, $currentUuid);
                                                        $despuesDeEste = $yaConSegundoPar + $cantidadActual;

                                                        if ($despuesDeEste > $permitidos) {
                                                            Notification::make()
                                                                ->title('Límite alcanzado')
                                                                ->body("Solo se puede aplicar 'Segundo Par' a {$permitidos} par(es) en total.")
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $this->restoreOriginalPrice($get, $set);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        if (! $this->esProductoMenorCostoElegible((int) $producto->id, $detalles)) {
                                                            Notification::make()
                                                                ->title('Regla del menor precio')
                                                                ->body('El descuento de "Segundo Par" solo aplica al producto de menor precio de venta en la orden.')
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $this->restoreOriginalPrice($get, $set);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        if (($producto->precio_segundo_par ?? 0) <= 0 || ($producto->precio_venta ?? 0) <= 0) {
                                                            Notification::make()
                                                                ->title('Descuento no aplicable')
                                                                ->body('Este producto no tiene porcentaje de "Segundo Par" o precio de venta configurado.')
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $this->restoreOriginalPrice($get, $set);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        $precioBase = $this->calcularPrecioSegundoPar($producto);
                                                        $descuento5 = (bool) $get('5%');
                                                        $precioFinal = $this->calcularPrecioDetalle((int) $producto->id, 'segundo_par', $cantidadActual, $descuento5);

                                                        $set('precio', $precioFinal);
                                                        $set('precio_base', $precioBase);
                                                        $set('subtotal', round($precioFinal * $cantidadActual, 2));
                                                        $this->updateOrderTotals($get, $set);

                                                        Notification::make()
                                                            ->title('Segundo Par aplicado')
                                                            ->body('Se aplicó el descuento del '.$producto->precio_segundo_par.'% sobre el precio de venta.')
                                                            ->success()->send();

                                                        return;
                                                    }

                                                    // Si seleccionan alguna oferta distinta a segundo_par (oferta, liquidacion, descuento)
                                                    if (in_array($state, ['oferta', 'liquidacion', 'descuento'])) {
                                                        $haySegundoParEnOtros = collect($detalles)
                                                            ->contains(function ($item) use ($currentUuid) {
                                                                return (($item['uuid'] ?? null) !== $currentUuid)
                                                                    && ($item['tipo_precio'] ?? null) === 'segundo_par';
                                                            });

                                                        $esMarchamoRojo = strtolower($producto->marchamo ?? '') === 'rojo';

                                                        if ($haySegundoParEnOtros && !($state === 'liquidacion' && $esMarchamoRojo)) {
                                                            Notification::make()
                                                                ->title('Conflicto de promociones')
                                                                ->body('No puedes aplicar esta promoción si ya hay productos con descuento de "Segundo Par".')
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $this->restoreOriginalPrice($get, $set);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        $precio = $producto->precio_venta;
                                                        switch ($state) {
                                                            case 'oferta':
                                                                $precio = ($producto->precio_oferta > 0) ? $producto->precio_oferta : $producto->precio_venta;
                                                                break;
                                                            case 'liquidacion':
                                                                $precio = $this->calcularPrecioLiquidacion($producto);
                                                                break;
                                                            case 'descuento':
                                                                if ($producto->precio_descuento > 0) {
                                                                    $precio = round($producto->precio_venta * (1 - ($producto->precio_descuento / 100)), 2);
                                                                } else {
                                                                    Notification::make()
                                                                        ->title('Descuento no aplicable')
                                                                        ->body('No hay porcentaje de descuento disponible para este producto.')
                                                                        ->danger()
                                                                        ->send();

                                                                    $set('tipo_precio', 'normal');
                                                                    $set('precio', $producto->precio_venta);
                                                                    $set('precio_base', $producto->precio_venta);
                                                                    $set('subtotal', round($producto->precio_venta * $cantidadActual, 2));
                                                                    $this->updateOrderTotals($get, $set);

                                                                    return;
                                                                }
                                                                break;
                                                        }

                                                        $descuento5 = (bool) $get('5%');
                                                        $precioFinal = $this->calcularPrecioDetalle((int) $producto->id, $state, $cantidadActual, $descuento5);

                                                        $set('precio', $precioFinal);
                                                        $set('precio_base', $precio);
                                                        $set('subtotal', round($precioFinal * $cantidadActual, 2));
                                                        $this->updateOrderTotals($get, $set);

                                                        return;
                                                    }

                                                    if ($state === 'apertura_20') {
                                                        $clienteId = $get('../../cliente_id');
                                                        $cliente = \App\Models\User::with('roles')->find($clienteId);
                                                        $roles = $cliente?->getRoleNames() ?? collect();

                                                        if (! $roles->contains('cliente_apertura')) {
                                                            Notification::make()
                                                                ->title('Descuento no disponible')
                                                                ->body('Solo los clientes de apertura pueden usar esta promoción.')
                                                                ->danger()
                                                                ->send();
                                                            $set('tipo_precio', 'normal');
                                                            $set('precio', $producto->precio_venta);
                                                            $set('precio_base', $producto->precio_venta);
                                                            $set('subtotal', round($producto->precio_venta * $cantidadActual, 2));
                                                            $set('oferta_cliente_20', false);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        $haySegundoParEnOtros = collect($detalles)
                                                            ->contains(function ($item) use ($currentUuid) {
                                                                return (($item['uuid'] ?? null) !== $currentUuid)
                                                                    && ($item['tipo_precio'] ?? null) === 'segundo_par';
                                                            });

                                                        if ($haySegundoParEnOtros) {
                                                            Notification::make()
                                                                ->title('Conflicto de promociones')
                                                                ->body('No puedes aplicar el descuento de "Cliente Apertura" si ya hay productos con descuento de "Segundo Par".')
                                                                ->danger()->send();

                                                            $set('tipo_precio', 'normal');
                                                            $set('precio', $producto->precio_venta);
                                                            $set('precio_base', $producto->precio_venta);
                                                            $set('subtotal', round($producto->precio_venta * $cantidadActual, 2));
                                                            $set('oferta_cliente_20', false);
                                                            $this->updateOrderTotals($get, $set);

                                                            return;
                                                        }

                                                        $precioBase = round($producto->precio_venta * 0.80, 2);
                                                        $descuento5 = (bool) $get('5%');
                                                        $precioFinal = $this->calcularPrecioDetalle((int) $producto->id, 'apertura_20', $cantidadActual, $descuento5);

                                                        $set('precio', $precioFinal);
                                                        $set('precio_base', $precioBase);
                                                        $set('subtotal', round($precioFinal * $cantidadActual, 2));
                                                        $set('oferta_cliente_20', true);

                                                        Notification::make()
                                                            ->title('Descuento aplicado')
                                                            ->body('Se aplicó el 20% de descuento del cliente apertura.')
                                                            ->success()
                                                            ->send();
                                                        $this->updateOrderTotals($get, $set);

                                                        return;
                                                    }

                                                    // si es 'normal' u otro, dejar precio por defecto
                                                    $precioBase = $producto->precio_venta;
                                                    $descuento5 = (bool) $get('5%');
                                                    $precioFinal = $this->calcularPrecioDetalle((int) $producto->id, 'normal', $cantidadActual, $descuento5);

                                                    $set('precio', $precioFinal);
                                                    $set('precio_base', $precioBase);
                                                    $set('subtotal', round($precioFinal * $cantidadActual, 2));
                                                    $this->updateOrderTotals($get, $set);
                                                })
                                                ->required()
                                                ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 2]),
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

                                                    $tipoPrecio = $get('tipo_precio') ?? 'normal';
                                                    $descuento5 = (bool) $get('5%');

                                                    $precioFinal = $this->calcularPrecioDetalle((int) $productoId, $tipoPrecio, (int) $state, $descuento5);

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
            }

            // Validar que no se mezclen promociones (segundo_par y apertura_20 u otras)
            $detallesData = $this->data['detalles'] ?? [];
            $productoIds = collect($detallesData)->pluck('producto_id')->filter()->unique();
            $productos = \App\Models\Producto::whereIn('id', $productoIds)->get()->keyBy('id');

            $esMarchamoRojoLiq = function ($d) use ($productos) {
                if (($d['tipo_precio'] ?? null) !== 'liquidacion') {
                    return false;
                }
                $p = $productos->get($d['producto_id'] ?? null);
                return $p && strtolower($p->marchamo ?? '') === 'rojo';
            };

            $tieneSegundoPar = collect($detallesData)->contains(fn ($d) => ($d['tipo_precio'] ?? null) === 'segundo_par');
            $tieneOtrasPromos = collect($detallesData)->contains(function ($d) use ($esMarchamoRojoLiq) {
                if ($esMarchamoRojoLiq($d)) {
                    return false;
                }
                return in_array($d['tipo_precio'] ?? null, ['oferta', 'liquidacion', 'descuento', 'apertura_20'], true);
            });

            if ($tieneSegundoPar && $tieneOtrasPromos) {
                throw ValidationException::withMessages([
                    'total' => 'No se puede combinar el descuento de "Segundo Par" con otras promociones o descuentos en la misma venta.',
                ]);
            }

            // Validar restricción de Marchamo Rojo: requiere un par a precio normal por cada par en liquidación.
            // Si también hay segundo_par, la suma de segundo_par y liquidaciones de Marchamo Rojo no puede exceder el total de pares a precio normal.
            $cantNormal = collect($detallesData)
                ->filter(fn ($d) => ($d['tipo_precio'] ?? null) === 'normal')
                ->sum(fn ($d) => (int) ($d['cantidad'] ?? 0));

            $cantSegundoPar = collect($detallesData)
                ->filter(fn ($d) => ($d['tipo_precio'] ?? null) === 'segundo_par')
                ->sum(fn ($d) => (int) ($d['cantidad'] ?? 0));

            $cantMarchamoRojoLiq = collect($detallesData)
                ->filter(fn ($d) => $esMarchamoRojoLiq($d))
                ->sum(fn ($d) => (int) ($d['cantidad'] ?? 0));

            if ($cantMarchamoRojoLiq > 0) {
                if ($cantSegundoPar + $cantMarchamoRojoLiq > $cantNormal) {
                    throw ValidationException::withMessages([
                        'total' => 'Para aplicar la liquidación de Marchamo Rojo a Q100 (o el descuento de Segundo Par), debe llevar un par a precio normal por cada par en promoción. Actualmente no tiene suficientes pares a precio normal.',
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

                if (in_array($tipoPagoPrincipal, [5, 9])) {
                    $this->record->update(['estado' => 'validacion_pago']);

                    Notification::make()
                        ->title('Venta registrada con pago pendiente')
                        ->body('El pago es por depósito o transferencia. Debe ser validado por un administrador antes de generar factura.')
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
