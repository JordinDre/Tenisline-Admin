<?php

namespace App\Filament\Inventario\Resources;

use App\Filament\Inventario\Resources\ProductoResource\Pages;
use App\Models\Observacion;
use App\Models\Producto;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Producto::class;

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?string $recordTitleAttribute = 'descripcion';

    protected static ?string $navigationIcon = 'tabler-layout-bottombar-filled';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $navigationGroup = 'Productos';

    protected static ?int $navigationSort = 1;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'restore',
            'delete',
            'view_costs',
            'view_supplier',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 3,
                ])
                    ->schema([
                        TextInput::make('nombre')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('descripcion')
                            ->required()
                            ->maxLength(250),
                        TextInput::make('modelo')
                            ->label('Linea/Modelo')
                            ->required()
                            ->maxLength(250),
                        TextInput::make('talla')
                            ->required()
                            ->maxLength(250),
                        Select::make('genero')
                            ->options([
                                'Hombre' => 'Hombre',
                                'Mujer' => 'Mujer',
                                'Niño' => 'Niño',
                                'Niña' => 'Niña',
                                'Bebés' => 'Bebés',
                                'Unisex' => 'Unisex',
                            ])
                            ->native(false)
                            ->required(),
                        TextInput::make('color')
                            ->maxLength(255),
                    ]),
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 4,
                ])
                    ->schema([
                        Select::make('proveedor_id')
                            ->searchable()
                            ->visible(auth()->user()->can('view_supplier_producto'))
                            ->relationship('proveedor', 'name', fn (Builder $query) => $query->role('proveedor')),
                        Select::make('marca_id')
                            ->required()
                            ->optionsLimit(12)
                            ->searchable()
                            ->relationship('marca', 'marca'),
                        DatePicker::make('fecha_ingreso')
                            ->label('Fecha de Ingreso'),
                    ]),
                Grid::make([
                    'default' => 1,
                    'sm' => 3,
                    'lg' => 5,
                ])
                    ->schema([
                        TextInput::make('precio_venta')
                            ->required()
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        TextInput::make('precio_vendedores')
                            ->required()
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        TextInput::make('precio_mayorista')
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),
                    ]),
                /* Repeater::make('escalas')
                    ->relationship()
                    ->visible(auth()->user()->can('view_costs_producto'))
                    ->schema([
                        TextInput::make('escala')
                            ->label('Escala')
                            ->required(),
                        TextInput::make('desde')
                            ->minValue(0)
                            ->required()
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        TextInput::make('hasta')
                            ->minValue(0)
                            ->required()
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        TextInput::make('porcentaje')
                            ->required()
                            ->minValue(0)
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->prefix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $precioCosto = floatval($get('../../precio_costo') ?? 0);
                                $porcentaje = floatval($get('porcentaje') ?? 0);
                                $precio = $precioCosto / (1 - ($porcentaje / 100));
                                $set('precio', round($precio, 2));
                            })
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        TextInput::make('precio')
                            ->required()
                            ->readOnly(),
                        TextInput::make('aumento')
                            ->minValue(0)
                            ->required()
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->default(0)
                            ->prefix('%'),
                        TextInput::make('descuento')
                            ->minValue(0)
                            ->required()
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->default(0)
                            ->prefix('%'),
                        TextInput::make('comision')
                            ->required()
                            ->minValue(0)
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->prefix('%'),
                        Select::make('role_id')
                            ->required()
                            ->relationship('role', 'name', fn (Builder $query) => $query->whereIn('name', User::ASESOR_ROLES))
                            ->preload()
                            ->searchable(),
                    ])->columnSpanFull()->columns([
                        'default' => 1,
                        'md' => 3,
                        'lg' => 5,
                    ]), */
                FileUpload::make('imagenes')
                    ->image()
                    ->downloadable()
                    ->label('Imágenes')
                    ->imageEditor()
                    ->multiple()
                    ->disk(config('filesystems.disks.s3.driver'))
                    ->directory(config('filesystems.default'))
                    ->visibility('public')
                    ->panelLayout('grid')
                    ->reorderable()
                    ->appendFiles()
                    ->maxSize(1024)
                    ->openable()
                    ->optimize('webp')
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        FileUpload::make('videos')
                            ->label('Videos')
                            ->multiple()
                            ->disk(config('filesystems.disks.s3.driver'))
                            ->directory(config('filesystems.default'))
                            ->visibility('public')
                            ->panelLayout('grid'),
                    ]),
                /* RichEditor::make('detalle')
                    ->columnSpanFull(), */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('imagenes')
                    ->label('Imágen')
                    ->formatStateUsing(function ($record): View {
                        return view('filament.tables.columns.image', [
                            'url' => config('filesystems.disks.s3.url').$record->imagenes[0],
                            'alt' => $record->descripcion,
                        ]);
                    }),
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->copyable()
                    ->searchable()
                    ->sortable(),/* 
                Tables\Columns\TextColumn::make('presentacion.presentacion')
                    ->copyable()
                    ->searchable()
                    ->sortable(), */
                Tables\Columns\TextColumn::make('marca.marca')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('proveedor.name')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_ingreso')
                    ->label('Fecha de Ingreso')
                    ->dateTime('d/m/Y')
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('marca_id')
                    ->relationship('marca', 'marca')
                    ->multiple()
                    ->searchable()
                    ->label('Marca'),
                /* SelectFilter::make('presentacion_id')
                    ->relationship('presentacion', 'presentacion')
                    ->multiple()
                    ->searchable()
                    ->label('Presentación'), */
                SelectFilter::make('proveedor_id')
                    ->relationship('proveedor', 'name')
                    ->multiple()
                    ->searchable()
                    ->label('Proveedor'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Desactivar')
                    ->visible(fn ($record) => auth()->user()->can('delete', $record))
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->form([
                        Textarea::make('observacion')
                            ->label('Observación')
                            ->minLength(5)
                            ->required(),
                    ])
                    ->action(function (array $data, Producto $record): void {
                        $observacion = new Observacion;
                        $observacion->observacion = $data['observacion'];
                        $observacion->user_id = auth()->user()->id;
                        $record->observaciones()->save($observacion);
                        $record->delete();

                        Notification::make()
                            ->title('Producto desactivado')
                            ->color('success')
                            ->success()
                            ->send();
                    })
                    ->modalContent(fn (Producto $record): View => view(
                        'filament.pages.actions.observaciones',
                        ['record' => $record],
                    ))
                    ->label('Desactivar'),
                Tables\Actions\RestoreAction::make(),
            ])
            /* ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Desactivar'),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]) */
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
            'index' => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'view' => Pages\ViewProducto::route('/{record}'),
            'edit' => Pages\EditProducto::route('/{record}/edit'),
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
