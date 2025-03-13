<?php

namespace App\Filament\Inventario\Resources;

use Closure;
use App\Models\Pago;
use Filament\Tables;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
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
                    ->required()
                    ->readOnly()
                    ->inputMode('decimal')
                    ->rule('numeric')
                    ->minValue(1),
                TextInput::make('total')
                    ->required()
                    ->inputMode('decimal')
                    ->rule('numeric')
                    ->minValue(1),
                Wizard::make([
                    Wizard\Step::make('Cliente')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Grid::make(3)
                                        ->relationship('factura')
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
                                            Hidden::make('user_id')
                                                ->default(auth()->user()->id),
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
                                        ->getOptionLabelFromRecordUsing(fn (Producto $record) => ProductoController::renderProductos($record, 'compra', null))
                                        ->allowHtml()
                                        ->searchable(['id'])
                                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                                            return ProductoController::searchProductos($search, 'compra', null);
                                        })
                                        ->optionsLimit(20)
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(['default' => 4, 'md' => 6, 'lg' => 4, 'xl' => 6])
                                        ->required(),
                                    TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->default(1)
                                        ->minValue(1)
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->live(onBlur: true)
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->required(),
                                    TextInput::make('precio')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->minValue(0)
                                        ->default(0)
                                        ->visible(auth()->user()->can('view_costs_producto'))
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->inputMode('decimal')
                                        ->rule('numeric'),
                                    /* TextInput::make('envio')
                                        ->label('Envío')
                                        ->inputMode('decimal')
                                        ->default(0)
                                        ->rule('numeric')
                                        ->live(onBlur: true)
                                        ->minValue(0)
                                        ->visible(auth()->user()->can('view_costs_producto'))
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2]),
                                    TextInput::make('envase')
                                        ->default(0)
                                        ->inputMode('decimal')
                                        ->rule('numeric')
                                        ->live(onBlur: true)
                                        ->minValue(0)
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->visible(auth()->user()->can('view_costs_producto'))
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            $precio = floatval($get('precio'));
                                            $envio = floatval($get('envio'));
                                            $envase = floatval($get('envase'));
                                            $cantidad = floatval($get('cantidad'));
                                            $subtotal = ($precio + $envio + $envase) * $cantidad;
                                            $set('subtotal', $subtotal);
                                        }), */
                                    Placeholder::make('subtotal')
                                        ->default(0)
                                        ->columnSpan(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 2])
                                        ->content(function (Get $get) {
                                            $subtotal = $get('cantidad') * $get('precio');

                                            return $subtotal;
                                        }),
                                ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Producto')
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    $detalles = $get('detalles');
                                    $subtotal = collect($detalles)->sum(function ($detalle) {
                                        $precio = (float) $detalle['precio'];
                                        $cantidad = (float) $detalle['cantidad'];

                                        return $precio  * $cantidad;
                                    });
                                    $set('subtotal', round($subtotal, 2));
                                }),
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
                                        ->searchable(),/* 
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
                                ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Pago'),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        ->action(fn (Compra $record) => CompraController::anular($record))
                        ->visible(fn ($record) => auth()->user()->can('annular', $record)),
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
