<?php

namespace App\Filament\Inventario\Resources\CompraResource\Pages;

use App\Enums\EstadoCompraStatus;
use App\Filament\Inventario\Resources\CompraResource;
use App\Models\Compra;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCompras extends ListRecords
{
    protected static string $resource = CompraResource::class;

    public function getTabs(): array
    {
        return collect(EstadoCompraStatus::cases())->mapWithKeys(function (EstadoCompraStatus $estado) {
            return [
                $estado->getLabel() => Tab::make()
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', $estado->value))
                    ->badge(Compra::query()->where('estado', $estado->value)->count())
                    ->badgeColor($estado->getColor()),
            ];
        })->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('createAutomatico')
                ->label('Crear compra automático')
                ->url(CompraResource::getUrl('create-automatico'))
                ->visible(auth()->user()->can('view_costs_producto')),
        ];
    }
}
