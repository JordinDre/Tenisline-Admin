<?php

namespace App\Filament\Inventario\Resources;

use App\Filament\Inventario\Resources\ProductoResource\Pages;
use App\Models\Bodega;
use App\Models\Escala;
use App\Models\Observacion;
use App\Models\Producto;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ProductoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Producto::class;

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

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
                        TextInput::make('codigo')
                            ->required()
                            ->maxLength(100)
                            ->unique(table: Producto::class, ignoreRecord: true)
                            ->rules(['regex:/^[^a-z]+$/'])
                            ->validationMessages([
                                'regex' => 'No se permiten letras minúsculas.',
                            ]),
                        Select::make('marchamo')
                            ->options([
                                'rojo' => 'ROJO',
                                'naranja' => 'NARANJA',
                                'celeste' => 'CELESTE',
                            ])
                            ->native(false)
                            ->required(),
                        TextInput::make('descripcion')
                            ->required()
                            ->maxLength(250)
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('descripcion', mb_strtoupper($state));
                            })
                            ->rules(['regex:/^[^a-z]+$/'])
                            ->validationMessages([
                                'regex' => 'No se permiten letras minúsculas.',
                            ]),

                        // TextInput::make('modelo')
                        //     ->label('Linea/Modelo')
                        //     ->required()
                        //     ->maxLength(250),
                        TextInput::make('talla')
                            ->required()
                            ->maxLength(250),
                        Select::make('genero')
                            ->options([
                                'CABALLERO' => 'CABALLERO',
                                'DAMA' => 'DAMA',
                                'INFANTE' => 'INFANTE',
                                'NIÑO' => 'NIÑO',
                            ])
                            ->native(false)
                            ->required(),
                        TextInput::make('color')
                            ->maxLength(255)
                            ->rules(['regex:/^[^a-z]+$/'])
                            ->validationMessages([
                                'regex' => 'No se permiten letras minúsculas.',
                            ]),
                    ]),
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 4,
                ])
                    ->schema([
                        // Select::make('proveedor_id')
                        //     ->searchable()
                        //     ->visible(auth()->user()->can('view_supplier_producto'))
                        //     ->relationship('proveedor', 'name', fn(Builder $query) => $query->role('proveedor')),
                        Select::make('marca_id')
                            ->required()
                            ->optionsLimit(12)
                            ->searchable()
                            ->relationship('marca', 'marca'),
                        /* Select::make('presentacion_id')
                            ->required()
                            ->searchable()
                            ->relationship('presentacion', 'presentacion'), */
                        TextInput::make('precio_costo')
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->readOnly()
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        TextInput::make('precio_liquidacion')
                            ->label('% Precio Liquidación')
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        TextInput::make('precio_oferta')
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),

                    ]),
                Grid::make([
                    'default' => 1,
                    'sm' => 3,
                    'lg' => 5,
                ])
                    ->schema([

                        /* TextInput::make('precio_descuento')
                            ->label('Descuento %')
                            ->required()
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'), */

                        TextInput::make('precio_segundo_par')
                            ->label('% Precio Segundo Par')
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),

                        TextInput::make('precio_venta')
                            ->label('Precio de Venta')
                            ->required()
                            ->live(onBlur: true)
                            ->minValue(0)
                            ->visible(auth()->user()->can('view_costs_producto'))
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        // TextInput::make('envio')
                        //     ->live(onBlur: true)
                        //     ->minValue(0)
                        //     ->visible(auth()->user()->can('view_costs_producto'))
                        //     ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        //         $set('precio_costo', round((float) $state + (float) $get('precio_compra'), 2));
                        //     })
                        //     ->inputMode('decimal')
                        //     ->rule('numeric'),

                    ]),
                Repeater::make('escalas')
                    ->relationship()
                    ->visible(auth()->user()->can('view_costs_producto'))
                    ->schema([
                        Select::make('dia')
                            ->label('Día')
                            ->options(function (callable $get) {
                                $productoId = $get('producto_id');

                                if (! $productoId) {
                                    return [
                                        'lunes' => 'Lunes',
                                        'martes' => 'Martes',
                                        'miercoles' => 'Miércoles',
                                        'jueves' => 'Jueves',
                                        'viernes' => 'Viernes',
                                        'sabado' => 'Sábado',
                                        'domingo' => 'Domingo',
                                    ];
                                }

                                $diasOcupados = Escala::whereIn('producto_id', (array) $productoId)->pluck('dia')->toArray();

                                $diasDisponibles = [
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miercoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sabado' => 'Sábado',
                                    'domingo' => 'Domingo',
                                ];

                                return array_diff_key($diasDisponibles, array_flip($diasOcupados));
                            })
                            ->reactive()
                            ->native(false)
                            ->required(),
                        TextInput::make('porcentaje')
                            ->required()
                            ->minValue(0)
                            ->inputMode('decimal')
                            ->rule('numeric')
                            ->prefix('%')
                            ->live(onBlur: true)
                            ->inputMode('decimal')
                            ->rule('numeric'),
                        Hidden::make('producto_id')
                            ->default(function (Get $get, ?Producto $record) {
                                if ($record) {
                                    return $record->id;
                                }

                                return null;
                            }),
                    ])->columnSpanFull()->columns([
                        'default' => 1,
                        'md' => 3,
                        'lg' => 5,
                    ])->afterStateHydrated(function (Repeater $component, ?Producto $record) {
                        if (! $record) {
                            $component->schema([
                                Select::make('dia')
                                    ->label('Día')
                                    ->options(function (callable $get) {
                                        $productoId = $get('producto_id');

                                        if (! $productoId) {
                                            return [
                                                'lunes' => 'Lunes',
                                                'martes' => 'Martes',
                                                'miercoles' => 'Miércoles',
                                                'jueves' => 'Jueves',
                                                'viernes' => 'Viernes',
                                                'sabado' => 'Sábado',
                                                'domingo' => 'Domingo',
                                            ];
                                        }

                                        $diasOcupados = Escala::whereIn('producto_id', (array) $productoId)->pluck('dia')->toArray();

                                        $diasDisponibles = [
                                            'lunes' => 'Lunes',
                                            'martes' => 'Martes',
                                            'miercoles' => 'Miércoles',
                                            'jueves' => 'Jueves',
                                            'viernes' => 'Viernes',
                                            'sabado' => 'Sábado',
                                            'domingo' => 'Domingo',
                                        ];

                                        return array_diff_key($diasDisponibles, array_flip($diasOcupados));
                                    })
                                    ->reactive()
                                    ->native(false)
                                    ->required(),
                                TextInput::make('porcentaje')
                                    ->required()
                                    ->minValue(0)
                                    ->inputMode('decimal')
                                    ->rule('numeric')
                                    ->prefix('%')
                                    ->live(onBlur: true)
                                    ->inputMode('decimal')
                                    ->rule('numeric'),
                                Hidden::make('producto_id')
                                    ->default(function (Get $get, Producto $record) {
                                        return $record->id;
                                    }),
                            ]);
                        }
                    }),
                FileUpload::make('imagenes')
                    ->image()
                    ->label('Imágenes')
                    ->imageEditor()
                    ->multiple()
                    ->disk(config('filesystems.disks.s3.driver'))
                    ->directory(config('filesystems.default'))
                    ->visibility('public')
                    ->maxSize(5000)
                    ->optimize('webp')
                    /* ->imageResizeMode('cover')
                    ->imageCropAspectRatio('9:16')
                    ->imageResizeTargetWidth('1080')
                    ->imageResizeTargetHeight('1920')
                    ->orientImagesFromExif(true) */
                    ->columnSpanFull(),
                // Grid::make(2)
                //     ->schema([
                //         FileUpload::make('videos')
                //             ->label('Videos')
                //             ->multiple()
                //             ->disk(config('filesystems.disks.s3.driver'))
                //             ->directory(config('filesystems.default'))
                //             ->visibility('public')
                //             ->panelLayout('grid'),
                //     ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make()->withFilename('Productos '.date('d-m-Y'))->fromTable(),
                ])->label('Exportar')->color('success')->visible(auth()->user()->can('view_costs_producto')),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('imagenes')
                    ->label('Imágen')
                    ->formatStateUsing(function ($record): View {
                        return view('filament.tables.columns.image', [
                            'url' => config('filesystems.disks.s3.url').$record->imagenes[0],
                            'alt' => $record->descripcion,
                        ]);
                    }),
                Tables\Columns\TextColumn::make('codigo')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('marchamo')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('marca.marca')
                    ->label('Marca')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('talla')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('genero')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('color')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_costo')
                    ->label('Costo')
                    ->formatStateUsing(function ($record) {
                        return number_format($record->precio_costo, 2);
                    })
                    ->copyable()
                    ->visible(auth()->user()->can('view_costs_producto'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_liquidacion')
                    ->label('Liquidación')
                    ->formatStateUsing(function ($record) {
                        return number_format($record->precio_liquidacion, 2);
                    })
                    ->copyable()
                    ->visible(auth()->user()->can('view_costs_producto'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_oferta')
                    ->label('Oferta')
                    ->formatStateUsing(function ($record) {
                        return number_format($record->precio_oferta, 2);
                    })
                    ->copyable()
                    ->visible(auth()->user()->can('view_costs_producto'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_segundo_par')
                    ->label('Segundo Par')
                    ->formatStateUsing(function ($record) {
                        return number_format($record->precio_segundo_par, 2);
                    })
                    ->copyable()
                    ->visible(auth()->user()->can('view_costs_producto'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Venta')
                    ->formatStateUsing(function ($record) {
                        return number_format($record->precio_venta, 2);
                    })
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                ...Bodega::whereNotIn('bodega', Bodega::TRASLADO_NAME)->orderByRaw('FIELD(id, 1, 5, 6, 7, 8, 9, 2, 3)')->get()->map(function ($bodega) {
                    $bodegaShort = match ($bodega->bodega) {
                        'Zacapa' => 'ZAC',
                        'Zacapa Bodega' => 'ZAC B.',
                        'Central Bodega' => 'CENTRAL',
                        'Mal estado' => 'MAL ESTADO',
                        'Chiquimula' => 'CHI',
                        'Chiquimula Bodega' => 'CHI B.',
                        'Esquipulas' => 'ESQ',
                        'Esquipulas Bodega' => 'ESQ B.',
                        default => self::acortarBodega($bodega->bodega)
                    };

                    return Tables\Columns\TextColumn::make("existencia_bodega_{$bodega->id}")
                        ->label($bodegaShort)
                        ->numeric()
                        ->sortable();
                })->all(),
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                // Filtro por bodegas
                SelectFilter::make('bodegas')
                    ->label('Bodegas')
                    ->multiple()
                    ->searchable()
                    ->options(Bodega::whereNotIn('bodega', Bodega::TRASLADO_NAME)->orderByRaw('FIELD(id, 1, 5, 6, 7, 8, 9, 2)')->get()->pluck('bodega', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('inventario', function (Builder $query) use ($data) {
                            $query->whereIn('bodega_id', $data['values'])
                                ->where('existencia', '>', 0);
                        });
                    }),

                // Filtro por marca
                SelectFilter::make('marca_id')
                    ->relationship('marca', 'marca')
                    ->multiple()
                    ->searchable()
                    ->label('Marca'),

                // Filtro por género
                SelectFilter::make('genero')
                    ->label('Género')
                    ->options([
                        'CABALLERO' => 'CABALLERO',
                        'DAMA' => 'DAMA',
                        'INFANTE' => 'INFANTE',
                        'NIÑO' => 'NIÑO',
                    ])
                    ->multiple(),

                // Filtro por color
                SelectFilter::make('color')
                    ->label('Color')
                    ->options(function () {
                        return Producto::whereNotNull('color')
                            ->where('color', '!=', '')
                            ->distinct()
                            ->pluck('color', 'color')
                            ->sort();
                    })
                    ->multiple()
                    ->searchable(),

                // Filtro por talla
                SelectFilter::make('talla')
                    ->label('Talla')
                    ->options(function () {
                        return Producto::whereNotNull('talla')
                            ->where('talla', '!=', '')
                            ->distinct()
                            ->pluck('talla', 'talla')
                            ->sort();
                    })
                    ->multiple()
                    ->searchable(),

                // Filtro por proveedor
                SelectFilter::make('proveedor_id')
                    ->relationship('proveedor', 'name')
                    ->multiple()
                    ->searchable()
                    ->label('Proveedor'),

                SelectFilter::make('precio_oferta')
                    ->label('Precio Oferta')
                    ->options(function () {
                        return Producto::whereNotNull('precio_oferta')
                            ->where('precio_oferta', '!=', '')
                            ->distinct()
                            ->pluck('precio_oferta', 'precio_oferta')
                            ->sort();
                    })
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('precio_liquidacion')
                    ->label('Precio Liquidacion')
                    ->options(function () {
                        return Producto::whereNotNull('precio_liquidacion')
                            ->where('precio_liquidacion', '!=', '')
                            ->distinct()
                            ->pluck('precio_liquidacion', 'precio_liquidacion')
                            ->sort();
                    })
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('precio_segundo_par')
                    ->label('Precio Segungo Par')
                    ->options(function () {
                        return Producto::whereNotNull('precio_segundo_par')
                            ->where('precio_segundo_par', '!=', '')
                            ->distinct()
                            ->pluck('precio_segundo_par', 'precio_segundo_par')
                            ->sort();
                    })
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('precio_descuento')
                    ->label('Precio Descuento')
                    ->options(function () {
                        return Producto::whereNotNull('precio_descuento')
                            ->where('precio_descuento', '!=', '')
                            ->distinct()
                            ->pluck('precio_descuento', 'precio_descuento')
                            ->sort();
                    })
                    ->multiple()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Desactivar')
                    ->visible(fn ($record) => auth()->user()->can('delete', $record))
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
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
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        foreach (Bodega::whereNotIn('bodega', Bodega::TRASLADO_NAME)->orderByRaw('FIELD(id, 1, 5, 6, 7, 8, 9, 2)')->get() as $bodega) {
            $query->withSum(
                ['inventario as existencia_bodega_'.$bodega->id => fn ($q) => $q->where('bodega_id', $bodega->id)],
                'existencia'
            );
        }

        $query->withSum('inventario as total_existencia', 'existencia')
            ->distinct()
            ->orderByDesc('total_existencia');

        return $query;
    }

    private static function acortarBodega(string $bodega): string
    {
        // Si contiene "BODEGA", lo reemplazamos por "B."
        $bodega = str_replace(' BODEGA', ' B.', $bodega);

        // Dividir en palabras y tomar las primeras letras de cada palabra
        $palabras = explode(' ', $bodega);
        $acortado = '';

        foreach ($palabras as $palabra) {
            if (strlen($palabra) > 3) {
                // Tomar las primeras 3 letras de palabras largas
                $acortado .= strtoupper(substr($palabra, 0, 3)).' ';
            } else {
                // Mantener palabras cortas como están
                $acortado .= strtoupper($palabra).' ';
            }
        }

        return trim($acortado);
    }
}
