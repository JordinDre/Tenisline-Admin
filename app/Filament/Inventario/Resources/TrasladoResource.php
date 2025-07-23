<?php

namespace App\Filament\Inventario\Resources;

use Filament\Tables;
use App\Models\Bodega;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Producto;
use App\Models\Traslado;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Actions;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Enums\ActionsPosition;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\TrasladoController;
use App\Filament\Inventario\Resources\TrasladoResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class TrasladoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Traslado::class;

    protected static ?string $modelLabel = 'Traslado';

    protected static ?string $pluralModelLabel = 'Traslados';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Traslados';

    protected static ?string $navigationIcon = 'tabler-transfer-out';

    protected static ?string $navigationGroup = 'Gestiones';

    protected static ?int $navigationSort = 2;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'collect',
            'prepare',
            'deliver',
            'annular',
            'confirm',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)
                    ->schema([
                        Select::make('salida_id')
                            ->relationship(
                                'salida',
                                'bodega',
                                fn (Builder $query) => $query->whereHas('user', function ($query) {
                                    $query->where('user_id', auth()->user()->id);
                                })
                            )
                            ->preload()
                            /* ->live() */
                            ->afterStateUpdated(function (Set $set) {
                                $set('detalles', []);
                            })
                            ->searchable()
                            ->disableOptionWhen(fn (string $value, Get $get): bool => $value == $get('entrada_id'))
                            ->columnSpan(2)
                            ->required(),
                        Select::make('entrada_id')
                            ->relationship(
                                'entrada',
                                'bodega',
                                fn (Builder $query) => $query->whereHas('user', function ($query) {
                                    $query->whereNotIn('bodega_id', [Bodega::TRASLADO, Bodega::MAL_ESTADO]);
                                })
                            )
                            ->preload()
                            ->searchable()
                            ->disableOptionWhen(fn (string $value, Get $get): bool => $value == $get('salida_id'))
                            ->columnSpan(2)
                            ->required(),
                    ]),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
                Fieldset::make('Productos')
                    ->schema([
                        Placeholder::make('tabla-productos')
                        ->content(function ($state, $livewire) {
                            $detalles = [];
                    
                            // Si estamos viendo o editando un traslado ya creado, usa el record
                            if (isset($livewire->record) && method_exists($livewire->record, 'detalles')) {
                                $detalles = $livewire->record->detalles->map(function ($detalle) {
                                    return [
                                        'producto_id' => $detalle->producto_id,
                                        'descripcion' => $detalle->producto->descripcion ?? 'N/A',
                                        'cantidad_enviada' => $detalle->cantidad_enviada,
                                    ];
                                })->toArray();
                            }
                    
                            // Si estamos creando, usamos el array local
                            if (property_exists($livewire, 'detalles') && is_array($livewire->detalles)) {
                                $detalles = $livewire->detalles;
                            }
                    
                            return view('filament.partials.tabla-productos', [
                                'detalles' => $detalles,
                            ]);
                        }),
                
                            Actions::make([
                                FormAction::make('agregar_producto')
                                    ->label('Agregar producto')
                                    ->icon('heroicon-o-plus')
                                    ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Inventario\Resources\TrasladoResource\Pages\CreateTraslado
            || $livewire instanceof \App\Filament\Inventario\Resources\TrasladoResource\Pages\EditTraslado)
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
                                    ])
                                    ->action(function (array $data, $livewire) {
                                        $producto = \App\Models\Producto::find($data['producto_id']);
                            
                                        if (! $producto) return;
                            
                                        $existe = collect($livewire->detalles)->contains(
                                            fn ($item) => $item['producto_id'] === $producto->id
                                        );
                            
                                        if ($existe) return;
                            
                                        $livewire->detalles[] = [
                                            'producto_id' => $producto->id,
                                            'descripcion' => $producto->descripcion,
                                            'cantidad_enviada' => $data['cantidad'],
                                        ];
                                    }),
                            ])
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extremePaginationLinks()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salida.bodega')
                    ->label('Bodega de Salida')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entrada.bodega')
                    ->label('Bodega de Entrada')
                    ->numeric()
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_pares')
                    ->label('Total Pares')
                    ->getStateUsing(fn (Traslado $record) => $record->detalles->sum('cantidad_enviada'))
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('emisor.name')
                    ->label('Emisor')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('receptor.name')
                    ->label('Receptor')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('piloto.name')
                    ->label('Piloto')
                    ->searchable()
                    ->numeric()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->searchable()
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
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->estado->value == 'creado'),
                    TableAction::make('comprobante')
                        ->icon('heroicon-o-document-arrow-down')
                        ->modalContent(fn (Traslado $record): View => view(
                            'filament.pages.actions.iframe',
                            [
                                'record' => $record,
                                'title' => 'Comprobante Traslado #'.$record->id,
                                'route' => route('pdf.comprobante.traslado', ['id' => $record->id]),
                                'open' => true,
                            ],
                        ))
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->slideOver()
                        ->stickyModalHeader()
                        ->modalSubmitAction(false),
                    TableAction::make('prepare')
                        ->label('Preparar')
                        ->color('success')
                        ->requiresConfirmation()
                        ->icon('tabler-packages')
                        ->action(fn (Traslado $record) => TrasladoController::preparar($record))
                        ->visible(fn ($record) => auth()->user()->can('prepare', $record)),
                    TableAction::make('collect')
                        ->label('Recolectar')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->icon('tabler-package-export')
                        ->action(fn (Traslado $record) => TrasladoController::recolectar($record))
                        ->visible(fn ($record) => auth()->user()->can('collect', $record)),
                    TableAction::make('deliver')
                        ->label('Entregar')
                        ->color('info')
                        ->requiresConfirmation()
                        ->icon('tabler-player-track-next-filled')
                        ->action(fn (Traslado $record) => TrasladoController::entregar($record))
                        ->visible(fn ($record) => auth()->user()->can('deliver', $record)),
                    TableAction::make('confirm')
                        ->label('Confirmar')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->url(fn (Traslado $record) => TrasladoResource::getUrl('confirm', ['record' => $record]))
                        ->visible(fn ($record) => auth()->user()->can('confirm', $record)),
                    TableAction::make('annular')
                        ->label('Anular')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(fn (Traslado $record) => TrasladoController::anular($record))
                        ->visible(fn ($record) => auth()->user()->can('annular', $record)),
                ])
                    ->link()
                    ->label('Acciones'),
            ], position: ActionsPosition::BeforeColumns)->poll('10s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTraslados::route('/'),
            'create' => Pages\CreateTraslado::route('/create'),
            'view' => Pages\ViewTraslado::route('/{record}'),
            'edit' => Pages\EditTraslado::route('/{record}/edit'),
            'confirm' => Pages\ConfirmTraslado::route('/{record}/confirm'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->bodegas->isNotEmpty()) {
            $bodegasIds = $user->bodegas->pluck('id');

            $query->where(function ($q) use ($bodegasIds) {
                $q->whereIn('entrada_id', $bodegasIds)
                    ->orWhereIn('salida_id', $bodegasIds);
            });
        }

        return $query;
    }
}
