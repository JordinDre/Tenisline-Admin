<?php

namespace App\Filament\Inventario\Resources\InventarioResource\Pages;

use App\Models\Kardex;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Producto;
use App\Models\Inventario;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Http\Controllers\OrdenController;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\ProductoController;
use App\Filament\Inventario\Resources\InventarioResource;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ajustar')
                ->label('Ajustar Inventario')
                ->visible(auth()->user()->can('adjust_inventario'))
                ->form([
                    Select::make('bodega_id')
                        ->label('Bodega')
                        /* ->options(Bodega::whereNotIn('id', [Bodega::TRASLADO])->pluck('bodega', 'id')) */
                        ->relationship(
                            'bodega',
                            'bodega',
                            fn (Builder $query) => $query
                             ->whereHas('user', fn ($q) => $q->where('user_id', auth()->id())
                             )
                             ->whereNotIn('bodega', ['Mal estado', 'Traslado'])
                             ->where('bodega', 'not like', '%bodega%')
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('producto_id', '');
                        })
                        ->required(),
                    Select::make('producto_id')
                        ->label('Producto')
                        ->relationship('producto', 'descripcion', fn ($query) => $query->with(['marca', 'presentacion', 'escalas']))
                        ->getOptionLabelFromRecordUsing(fn (Producto $record, Get $get) => ProductoController::renderProductos($record, 'ajustar', $get('bodega_id')))
                        ->allowHtml()
                        ->searchable(['id'])
                        ->getSearchResultsUsing(function (string $search, Get $get): array {
                            return ProductoController::searchProductos($search, 'ajustar', $get('bodega_id'));
                        })
                        ->optionsLimit(20)
                        ->required(),
                    TextInput::make('cantidad')
                        ->label('Cantidad')
                        ->inputMode('decimal')
                        ->rule('numeric')
                        ->required(),
                    Textarea::make('observaciones')
                        ->required()
                        ->label('Observaciones')
                        ->minLength(15),
                ])
                ->action(function (array $data): void {
                    try {
                        DB::transaction(function () use ($data) {
                            $inventario = Inventario::firstOrCreate(
                                [
                                    'bodega_id' => $data['bodega_id'],
                                    'producto_id' => $data['producto_id'],
                                ],
                                [
                                    'existencia' => 0,
                                ]
                            );

                            $existenciaInicial = $inventario->existencia;
                            $nuevaExistencia = $existenciaInicial + $data['cantidad'];

                            if ($nuevaExistencia < 0) {
                                throw new \Exception('La existencia no puede ser menor a 0.');
                            }

                            $inventario->existencia = $nuevaExistencia;
                            $inventario->save();

                            $evento = $data['cantidad'] > 0 ? 'entrada' : 'salida';
                            $descripcion = $data['observaciones'];

                            Kardex::registrar(
                                $data['producto_id'],
                                $data['bodega_id'],
                                abs($data['cantidad']),
                                $existenciaInicial,
                                $nuevaExistencia,
                                $evento,
                                $inventario,
                                $descripcion
                            );
                        });
                        /* OrdenController::actualizarBackorder(); */
                        Notification::make()
                            ->title('Ajuste Realizado')
                            ->color('success')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al realizar el ajuste')
                            ->body($e->getMessage())
                            ->danger()
                            ->color('danger')
                            ->send();
                    }
                }),
        ];
    }
}
