<?php

namespace App\Filament\Ventas\Resources\VentaResource\Pages;

use Closure;
use App\Models\Pago;
use App\Models\User;
use App\Models\Escala;
use App\Models\Factura;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Producto;
use App\Models\TipoPago;
use Filament\Forms\Form;
use App\Models\Inventario;
use Filament\Forms\Components\Grid;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
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
use Filament\Forms\Components\Actions\Action;
use App\Filament\Ventas\Resources\VentaResource;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

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
                        Select::make('bodega_id')
                        ->relationship(
                            'bodega',
                            'bodega',
                            fn(Builder $query) => $query->whereHas('user', function ($query) {
                                $query->where('user_id', auth()->user()->id);
                            })
                        )
                            ->preload()
                            ->default(1)
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('detalles', []);
                            })
                            ->searchable()
                            ->required(),
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
                Wizard::make([
                    /* Wizard\Step::make('Cliente')
                        ->schema([
                            Select::make('cliente_id')
                                ->label('Cliente')
                                ->relationship('cliente', 'name', fn (Builder $query) => $query->role(['cliente', 'mayorista']))
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
                    Wizard\Step::make('Cliente y Productos')
                        ->schema([
                            Select::make('cliente_id')
                                ->label('Cliente')
                                ->relationship('cliente', 'name', fn (Builder $query) => $query->role(['cliente', 'mayorista']))
                                ->optionsLimit(20)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('tipo_pago_id', null);
                                })
                                ->searchable(),
                            Textarea::make('observaciones')
                                ->columnSpanFull(),
                            Repeater::make('detalles')
                                ->label('')
                                ->relationship()
                                ->defaultItems(0)
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
                                        ->searchable(['id', 'codigo', 'descripcion', 'marca.marca', 'presentacion.presentacion', 'codigo', 'modelo']) 
                                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                                            return ProductoController::searchProductos($search, 'venta', $get('../../bodega_id'), $get('../../cliente_id'));
                                        })
                                        ->optionsLimit(20)
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 1, 'xl' => 6])
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state && $get('../../bodega_id')) { 
                                                $productoId = $state;
                                    
                                                $escala = ProductoController::getEscalaPrecio(
                                                    productoId: $productoId,
                                                );
                                    
                                                $producto = Producto::find($productoId); 
                                                $precioBaseVenta = $producto->precio_venta;
                                                $precioMayorista = $producto->precio_mayorista; 
                                                $porcentajeDescuento = 0; 
                                                
                                                $cliente_id_en_formulario = $get('../../cliente_id'); // Obtener cliente_id del formulario
                                                $clienteRol = null;  

                                                if ($cliente_id_en_formulario) {
                                                    $cliente = User::find($cliente_id_en_formulario);
                                                    $clienteRol = $cliente?->getRoleNames()->first(); // **Obtener el rol del cliente**
                                                }
                                                
                                                if ($clienteRol === 'mayorista') {
                                                    $precioFinalParaSetear = $precioMayorista; // Usar precio mayorista
                                                } else {
                                                    $precioFinalParaSetear = $precioBaseVenta; // Usar precio venta normal
                                    
                                                    if ($escala) { // Aplicar escala solo si es cliente normal (o sin cliente mayorista)
                                                        $porcentajeDescuento = $escala->porcentaje;
                                                        $precioFinalParaSetear = round($precioBaseVenta * (1 - ($escala->porcentaje / 100)), 2);
                                                    }
                                                }
                                                $set('escala_id', $escala ? $escala->id : null);
                                                $set('precio', $precioFinalParaSetear); // **Usar $precioFinalParaSetear para setear el precio**
                                                $set('precio_comp', $producto->precio_costo);
                                                $set('descuento_porcentaje', $porcentajeDescuento); 
                                                $cantidad = $get('cantidad') ?? 1; 
                                                $set('subtotal', round((float) $precioFinalParaSetear * (float) $cantidad, 2));
                                                return;
                                    
                                            }
                                            // Limpiar campos si no se encuentra escala o se deselecciona producto
                                            $set('escala_id', null);
                                            $set('precio', 0);
                                            $set('precio_comp', null);
                                            $set('subtotal', 0);
                                            $set('descuento_porcentaje', 0); // Limpiar porcentaje de descuento también
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
                                        ->default(0)
                                        ->minValue(1)
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                $invetario = Inventario::where('producto_id', $get('producto_id'))->where('bodega_id', $get('../../../bodega_id'))->first();
                                                if ($invetario && $value > $invetario->existencia) {
                                                    $fail('La cantidad de productos no puede ser mayor al inventario. Exist: '.$invetario->existencia);
                                                }
                                    
                                                $cliente_id_en_formulario = $get('../../cliente_id'); 
                                                $clienteRol = null;
                                                if ($cliente_id_en_formulario) {
                                                    $cliente = User::find($cliente_id_en_formulario);
                                                    $clienteRol = $cliente?->getRoleNames()->first();
                                                }
                                    
                                                if ($clienteRol === 'mayorista' && $value < 10) {
                                                    $fail('Para clientes mayoristas, la cantidad mínima por producto es 10.');
                                                }
                                            },
                                        ])
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $set('subtotal', round((float) $state * (float) $get('precio'), 2));
                                            /* $set('ganancia', round((float) $state * (float) $get('precio') * ($get('comision') / 100), 2)); */
                                        })
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->required(),
                                    TextInput::make('precio')
                                        ->label('Precio')
                                        ->live(onBlur: true)
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
                                    /* TextInput::make('comision')
                                        ->label('Comisión (%)')
                                        ->readOnly()
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]), */
                                    Hidden::make('escala_id'),
                                    Hidden::make('precio_comp'),
                                    /* Hidden::make('ganancia'), */
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
                                        $precio = is_numeric($detalle['precio']) ? (float) $detalle['precio'] : 0; 
                                        $cantidad = is_numeric($detalle['cantidad']) ? (float) $detalle['cantidad'] : 0; 

                                        return $precio * $cantidad;
                                    });
                                    $set('subtotal', round($subtotal, 2));
                                    $get('subtotal') >= Factura::CF || $set('facturar_cf', false);
                                    /* $get('subtotal') >= Factura::CF || $set('comp', false); */
                                    $set('total', round($subtotal, 2));
                                })->visible(fn (Get $get): bool => !empty($get('bodega_id'))),
                                
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
                                        ->searchable()
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                if ($get('total') < collect($get('pagos'))->sum('monto') && $value == 4) {
                                                    $fail('El monto total de los pagos no puede ser mayor al total de la orden.');
                                                }
                                            },
                                        ])
                                        ->preload(),
                                    Toggle::make('facturar_cf')
                                        ->inline(false)
                                        ->live()
                                        ->disabled(fn (Get $get) => $get('total') >= Factura::CF)
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            if (! $get('facturar_cf')) {
                                                $set('comp', false);
                                            }
                                        })
                                        ->label('Facturar CF'),
                                    /* Toggle::make('comp')
                                        ->inline(false)
                                        ->label('Comp')
                                        ->disabled(fn (Get $get) => $get('facturar_cf') == false || $get('total') >= Factura::CF), */
                                ]),
                            Repeater::make('pagos')
                                ->label('')
                                ->relationship()
                                ->minItems(function (Get $get) {
                                    return $get('tipo_pago_id') == 4 ? 1 : 0;
                                })
                                ->visible(fn (Get $get) => $get('tipo_pago_id') == 4)
                                ->defaultItems(0)
                                ->columns(7)
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Forma de Pago')
                                        ->relationship('tipoPago', 'tipo_pago', fn (Builder $query) => $query->whereIn('tipo_pago', TipoPago::FORMAS_PAGO))
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
                                        ->label('No. Documento')
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
                                        ->optimize('webp'),
                                ])
                                ->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Pago'),
                        ]),
                ])->skippable()->columnSpanFull(),
            ]);
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
            if ($this->record->tipo_pago_id == 2) {
                UserController::sumarSaldo(User::find($this->data['cliente_id']), $this->data['total']);
            }
            VentaController::facturar($this->record);
            Notification::make()
                ->title('Venta registrada correctamente')
                ->success()
                ->color('success')
                ->send();
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
