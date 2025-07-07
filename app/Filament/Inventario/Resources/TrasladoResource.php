<?php

namespace App\Filament\Inventario\Resources;

use App\Filament\Inventario\Resources\TrasladoResource\Pages;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\TrasladoController;
use App\Models\Bodega;
use App\Models\Producto;
use App\Models\Traslado;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

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
                            ->relationship('producto', 'descripcion', function ($query) {
                                $query->withTrashed(); // Incluir productos eliminados
                            })
                            ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductosBasico($record, '', $get('../../salida_id')))
                            ->allowHtml()
                            ->searchable(['id'])
                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                return ProductoController::searchProductosBasico($search, '', $get('../../salida_id'));
                            })
                            ->optionsLimit(20)
                            ->columnSpanFull()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->required(),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('cantidad_enviada')
                                    ->label('Cantidad Enviada')
                                    ->minValue(1)
                                    ->inputMode('decimal')
                                    ->rule('numeric')
                                    ->required(),
                                TextInput::make('cantidad_recibida')
                                    ->label('Cantidad Recibida')
                                    ->visibleOn('view'),
                            ]),
                    ])->collapsible()->columnSpanFull()->reorderableWithButtons()->reorderable()->addActionLabel('Agregar Producto'),
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
                    Action::make('comprobante')
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
                    Action::make('prepare')
                        ->label('Preparar')
                        ->color('success')
                        ->requiresConfirmation()
                        ->icon('tabler-packages')
                        ->action(fn (Traslado $record) => TrasladoController::preparar($record))
                        ->visible(fn ($record) => auth()->user()->can('prepare', $record)),
                    Action::make('collect')
                        ->label('Recolectar')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->icon('tabler-package-export')
                        ->action(fn (Traslado $record) => TrasladoController::recolectar($record))
                        ->visible(fn ($record) => auth()->user()->can('collect', $record)),
                    Action::make('deliver')
                        ->label('Entregar')
                        ->color('info')
                        ->requiresConfirmation()
                        ->icon('tabler-player-track-next-filled')
                        ->action(fn (Traslado $record) => TrasladoController::entregar($record))
                        ->visible(fn ($record) => auth()->user()->can('deliver', $record)),
                    Action::make('confirm')
                        ->label('Confirmar')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->url(fn (Traslado $record) => TrasladoResource::getUrl('confirm', ['record' => $record]))
                        ->visible(fn ($record) => auth()->user()->can('confirm', $record)),
                    Action::make('annular')
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
