<?php

namespace App\Filament\Inventario\Resources;

use Closure;
use App\Models\Pago;
use Filament\Tables;
use App\Models\Banco;
use App\Models\Compra;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Producto;
use App\Models\TipoPago;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\CompraController;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Enums\ActionsPosition;
use App\Http\Controllers\ProductoController;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Inventario\Resources\CompraResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class CompraResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Compra::class;

    protected static ?string $modelLabel = 'Compra';

    protected static ?string $pluralModelLabel = 'Compras';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Compras';

    protected static ?string $navigationIcon = 'tabler-shopping-cart-down';

    protected static ?string $navigationGroup = 'Gestiones';

    protected static ?int $navigationSort = 1;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'confirm',
            'annular',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->required()
                    ->readOnly(),
                TextInput::make('total')
                    ->label('Total')
                    ->required()
                    ->readOnly(),
                Wizard::make([
                    Wizard\Step::make('Productos')
                        ->schema([
                            Fieldset::make('Productos')
                                ->schema([
                                    Placeholder::make('tabla_productos')
                                        ->content(fn ($livewire) => view('filament.partials.tabla-productos-compras', [
                                            'detalles' => $livewire->detalles,
                                            'livewire' => $livewire,
                                        ])),

                                    Actions::make([
                                        FormAction::make('agregar_producto')
                                            ->label('Agregar producto')
                                            ->icon('heroicon-o-plus-circle')
                                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Inventario\Resources\CompraResource\Pages\CreateCompra
            || $livewire instanceof \App\Filament\Inventario\Resources\CompraResource\Pages\EditCompra)
                                            ->color('primary')
                                            ->form([
                                                Select::make('producto_id')
                                                    ->label('Producto')
                                                    ->options(\App\Models\Producto::pluck('descripcion', 'id')->toArray())
                                                    ->required(),
                                                TextInput::make('cantidad')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->required(),
                                                TextInput::make('precio')
                                                    ->label('Precio Costo')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->required()
                                                    ->visible(auth()->user()->can('view_costs_producto')),
                                                TextInput::make('precio_venta')
                                                    ->label('Precio Venta')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->required()
                                                    ->visible(auth()->user()->can('view_costs_producto')),
                                            ])
                                            ->action(function (array $data, $livewire , Set $set) {
                                                $producto = \App\Models\Producto::find($data['producto_id']);
                                                if (!$producto) return;

                                                // Prevenir duplicados
                                                $existe = collect($livewire->detalles)->contains('producto_id', $producto->id);
                                                if ($existe) return;

                                                $livewire->detalles[] = [
                                                    'producto_id' => $producto->id,
                                                    'descripcion' => $producto->descripcion,
                                                    'cantidad' => $data['cantidad'],
                                                    'precio' => $data['precio'],
                                                    'precio_venta' => $data['precio_venta'],
                                                ];

                                                // Calcular subtotal y total directamente aquí
                                                $subtotal = collect($livewire->detalles)->sum(fn ($d) => $d['cantidad'] * $d['precio']);
                                                $total = $subtotal; 

                                                $set('subtotal', round($subtotal, 2));
                                                $set('total', round($subtotal, 2));
                                            }),
                                    ])->columnSpanFull(),
                                ]),
                        ]),
                    Wizard\Step::make('Cliente')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Grid::make(3)
                                        //->relationship('factura')
                                        ->schema([
                                            TextInput::make('fel_uuid')
                                                ->required()
                                                ->label('No. Autorización'),
                                            TextInput::make('fel_numero')
                                                ->required()
                                                ->label('No. DTE'),
                                            TextInput::make('fel_serie')
                                                ->required()
                                                ->label('No. Serie'),
                                        ])->columnSpan(3),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    Select::make('bodega_id')
                                        ->relationship('bodega', 'bodega')
                                        ->preload()
                                        ->searchable()
                                        ->required(),
                                    Select::make('proveedor_id')
                                        ->relationship('proveedor', 'name', fn (Builder $query) => $query->role('proveedor'))
                                        ->searchable(),
                                ]),
                        ]),
                    Wizard\Step::make('Pagos')
                        ->schema([
                            Grid::make([
                                'default' => 2,
                            ])
                                ->schema([
                                    Select::make('tipo_pago_id')
                                        ->label('Tipo de Pago')
                                        ->relationship('tipoPago', 'tipo_pago')
                                        ->preload()
                                        ->placeholder('Seleccione')
                                        ->live()
                                        ->searchable(), /*
                                    TextInput::make('dias_credito')
                                        ->label('Días de Crédito')
                                        ->minValue(1)
                                        ->visible(fn (Get $get) => $get('tipo_pago_id') == 2)
                                        ->required(fn (Get $get) => $get('tipo_pago_id') == 2)
                                        ->inputMode('decimal')
                                        ->rule('numeric'), */
                                ]),
                            Repeater::make('pagos')
                                ->label('')
                                ->relationship()
                                ->minItems(function (Get $get) {
                                    return $get('tipo_pago_id') == 4 ? 1 : 0;
                                })
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
                                    Hidden::make('user_id')
                                        ->default(auth()->user()->id),
                                    TextInput::make('no_documento')
                                        ->label('No. Documento')->rules([
                                            fn (Get $get, $livewire): Closure => function (string $attribute, $value, Closure $fail) use ($get, $livewire) {
                                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                                    return; 
                                                }

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
                                        ->relationship(
                                            'banco',
                                            'banco',
                                            function ($query) {
                                                return $query->whereIn('banco', Banco::BANCOS_DISPONIBLES);
                                            }
                                        )
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
                                        ->maxSize(5000)
                                        ->resize(50)
                                        ->openable()
                                        ->columnSpan(['sm' => 1, 'md' => 3])
                                        ->optimize('webp'),
                                ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Pago'),
                        ]),
                ])->skippable()->columnSpanFull(),
            ])->live();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')->badge(),
                Tables\Columns\TextColumn::make('factura.fel_uuid')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->label('No. Autorización'),
                Tables\Columns\TextColumn::make('factura.fel_numero')
                    ->label('No. DTE')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('factura.fel_serie')
                    ->label('No. Serie')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bodega.bodega')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_pares')
                    ->label('Total Pares')
                    ->getStateUsing(fn (Compra $record) => $record->detalles->sum('cantidad'))
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('proveedor.name')
                    ->numeric()
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i:s')
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->filters([])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Action::make('confirm')
                        ->label('Confirmar')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Compra $record) => CompraController::confirmar($record))
                        ->visible(fn ($record) => auth()->user()->can('confirm', $record)),
                    Action::make('annular')
                        ->label('Anular')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(fn(Compra $record) => CompraController::anular($record))
                        ->visible(fn($record) => auth()->user()->can('annular', $record)),
                    Action::make('completar')
                        ->label('Completar')
                        ->color('secondary')
                        ->icon('heroicon-o-check')
                        ->action(fn(Compra $record) => CompraController::completar($record))
                        ->visible(fn($record) => auth()->user()->can('complete', $record)),
                ])
                    ->link()
                    ->label('Acciones'),
            ], position: ActionsPosition::BeforeColumns)->poll('10s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
            'view' => Pages\ViewCompra::route('/{record}'),
            'edit' => Pages\EditCompra::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
